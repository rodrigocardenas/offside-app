<?php

namespace App\Http\Controllers\Api;

use App\Models\PreMatch;
use App\Models\PreMatchProposition;
use App\Models\PreMatchVote;
use App\Models\PreMatchResolution;
use App\Models\GroupPenalty;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PreMatchController extends Controller
{
    /**
     * GET /api/pre-matches
     * Listar todos los desafíos Pre Match de un grupo
     */
    public function index(Request $request): JsonResponse
    {
        $groupId = $request->query('group_id');
        $status = $request->query('status'); // pending, active, completed

        $query = PreMatch::query();

        if ($groupId) {
            $query->where('group_id', $groupId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $preMatches = $query->with(['match', 'group', 'creator', 'propositions.votes'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($preMatches);
    }

    /**
     * POST /api/pre-matches
     * Crear nuevo desafío Pre Match (Admin only)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'match_id' => 'required|exists:matches,id',
            'group_id' => 'required|exists:groups,id',
            'penalty_type' => 'required|in:POINTS,SOCIAL,REVANCHA',
            'penalty_points' => 'nullable|integer|min:100|max:5000',
            'penalty_description' => 'nullable|string|max:500',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $validated['created_by'] = auth()->id();

        $preMatch = PreMatch::create($validated);

        return response()->json($preMatch->load('match', 'group', 'creator'), 201);
    }

    /**
     * GET /api/pre-matches/{id}
     * Obtener detalle de desafío Pre Match
     */
    public function show(PreMatch $preMatch): JsonResponse
    {
        return response()->json(
            $preMatch->load('match', 'group', 'creator', 'propositions.votes')
        );
    }

    /**
     * PATCH /api/pre-matches/{id}
     * Actualizar estado o detalles de desafío (Admin)
     */
    public function update(Request $request, PreMatch $preMatch): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'in:pending,active,completed,cancelled',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $preMatch->update($validated);

        return response()->json($preMatch->fresh());
    }

    /**
     * POST /api/pre-matches/{id}/propositions
     * Crear propuesta de acción
     */
    public function addProposition(Request $request, PreMatch $preMatch): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
        ]);

        $proposition = $preMatch->propositions()->create([
            ...$validated,
            'user_id' => auth()->id(),
            'validation_status' => 'pending',
            'votes_count' => 0,
            'approved_votes' => 0,
        ]);

        return response()->json($proposition, 201);
    }

    /**
     * POST /api/pre-match-propositions/{id}/vote
     * Votar en proposición (aprobar/rechazar)
     */
    public function voteOnProposition(Request $request, PreMatchProposition $proposition): JsonResponse
    {
        $validated = $request->validate([
            'approved' => 'required|boolean',
        ]);

        // Evitar votos múltiples del mismo usuario
        PreMatchVote::firstOrCreate(
            [
                'pre_match_proposition_id' => $proposition->id,
                'user_id' => auth()->id(),
            ],
            ['approved' => $validated['approved']]
        );

        // Actualizar conteos
        $totalVotes = $proposition->votes()->count();
        $approvedCount = $proposition->votes()->where('approved', true)->count();

        $proposition->update([
            'votes_count' => $totalVotes,
            'approved_votes' => $approvedCount,
            'approval_percentage' => $totalVotes > 0 ? ($approvedCount / $totalVotes) * 100 : 0,
        ]);

        return response()->json($proposition);
    }

    /**
     * POST /api/pre-matches/{id}/resolve
     * Resolver desafío (Admin): especificar si se cumplió y qué proposición ganó
     */
    public function resolvePreMatch(Request $request, PreMatch $preMatch): JsonResponse
    {
        $validated = $request->validate([
            'winning_proposition_id' => 'nullable|exists:pre_match_propositions,id',
            'was_fulfilled' => 'required|boolean',
            'admin_evidence' => 'nullable|string|max:1000',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        // Crear resolución
        $resolution = $preMatch->resolutions()->create([
            ...$validated,
            'admin_verified' => true,
            'resolved_at' => now(),
        ]);

        // Si fue rechazado, crear penalizaciones para proponentes
        if (!$validated['was_fulfilled']) {
            $this->applyPenalties($preMatch);
        }

        $preMatch->update(['status' => 'completed']);

        return response()->json($resolution);
    }

    /**
     * Aplicar penalizaciones a usuarios que propusieron acciones no cumplidas
     */
    private function applyPenalties(PreMatch $preMatch): void
    {
        foreach ($preMatch->propositions as $proposition) {
            GroupPenalty::create([
                'group_id' => $preMatch->group_id,
                'user_id' => $proposition->user_id,
                'pre_match_id' => $preMatch->id,
                'penalty_type' => $preMatch->penalty_type,
                'penalty_points' => $preMatch->penalty_points,
                'penalty_description' => $preMatch->penalty_description,
                'is_resolved' => false,
            ]);
        }
    }

    /**
     * GET /api/pre-matches/{id}/penalties
     * Listar penalizaciones asociadas
     */
    public function getPenalties(PreMatch $preMatch): JsonResponse
    {
        $penalties = $preMatch->penalties()->with('user', 'group')->paginate(20);
        return response()->json($penalties);
    }
}

