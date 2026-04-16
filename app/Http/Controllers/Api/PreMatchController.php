<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PreMatch;
use App\Models\PreMatchProposition;
use App\Models\PreMatchVote;
use App\Models\PreMatchResolution;
use App\Models\GroupPenalty;
use App\Services\PreMatchEventService;
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
            'football_match_id' => 'required|exists:football_matches,id',
            'group_id' => 'required|exists:groups,id',
            'penalty_type' => 'required|in:POINTS,SOCIAL',
            'penalty_points' => 'nullable|integer|min:100|max:5000',
            'penalty_description' => 'nullable|string|max:500',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['status'] = 'pending';

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
            'description' => 'nullable|string|max:1000',
        ]);

        $proposition = $preMatch->propositions()->create([
            ...$validated,
            'user_id' => auth()->id(),
            'validation_status' => 'pending',
            'votes_count' => 1, // Ya tiene mi voto
            'approved_votes' => 1, // Yo lo aprobé automáticamente
        ]);

        // Crear automáticamente mi voto de aceptación
        PreMatchVote::create([
            'pre_match_proposition_id' => $proposition->id,
            'user_id' => auth()->id(),
            'approved' => true,
        ]);

        // Calcular porcentaje
        $groupMembersCount = $preMatch->group->users->count();
        $approvalPercentage = (1 / $groupMembersCount) * 100;
        $proposition->update(['approval_percentage' => $approvalPercentage]);

        // ✨ GENERAR EVENTO: Nueva proposición creada
        PreMatchEventService::propositionCreated($proposition);

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

        // Contar miembros del grupo
        $groupMembersCount = $proposition->preMatch->group->users->count();

        // Determinar estado: si tiene todas las aprobaciones, cambiar a approved
        $validationStatus = $approvedCount >= $groupMembersCount ? 'approved' : 'pending';

        $proposition->update([
            'votes_count' => $totalVotes,
            'approved_votes' => $approvedCount,
            'approval_percentage' => $totalVotes > 0 ? ($approvedCount / $totalVotes) * 100 : 0,
            'validation_status' => $validationStatus,
        ]);

        // ✨ GENERAR EVENTO: Nuevo voto registrado
        PreMatchEventService::voteCreated($proposition, auth()->id(), $validated['approved']);

        // ✨ GENERAR EVENTO: Si fue auto-aprobada por unanimidad
        if ($validationStatus === 'approved' && $approvedCount >= $groupMembersCount) {
            PreMatchEventService::propositionAutoApproved($proposition);
        }

        // Verificar si TODAS las propuestas del pre-match están aprobadas
        $preMatch = $proposition->preMatch;
        $totalPropositions = $preMatch->propositions()->count();
        $approvedPropositions = $preMatch->propositions()->where('validation_status', 'approved')->count();

        // Si todas las propuestas están aprobadas, cambiar estado del pre-match a 'active'
        if ($totalPropositions > 0 && $approvedPropositions === $totalPropositions) {
            $oldStatus = $preMatch->status;
            $preMatch->update(['status' => 'active']);
            // ✨ GENERAR EVENTO: Status cambió a active
            PreMatchEventService::statusChanged($preMatch, $oldStatus, 'active');
        }

        // ✅ NUEVO: Obtener datos desglosados de votos con avatares
        $approvalVotes = $proposition->votes()
            ->where('approved', true)
            ->with('user:id,name,avatar')
            ->get();

        $rejectionVotes = $proposition->votes()
            ->where('approved', false)
            ->with('user:id,name,avatar')
            ->get();

        return response()->json([
            'proposition' => $proposition,
            'approval_count' => $approvalVotes->count(),
            'rejection_count' => $rejectionVotes->count(),
            'total_votes' => $totalVotes,
            'approval_percentage' => round($proposition->approval_percentage),
            'approvers' => $approvalVotes->map(fn($v) => [
                'id' => $v->user->id,
                'name' => $v->user->name,
                'avatar' => $v->user->avatar,
            ]),
            'rejectors' => $rejectionVotes->map(fn($v) => [
                'id' => $v->user->id,
                'name' => $v->user->name,
                'avatar' => $v->user->avatar,
            ])
        ]);
    }

    /**
     * DELETE /api/pre-match-propositions/{id}
     * Eliminar propuesta (solo el creador, si no está completamente aprobada)
     */
    public function deleteProposition(PreMatchProposition $proposition): JsonResponse
    {
        // Solo el creador puede eliminar
        if ($proposition->user_id !== auth()->id()) {
            return response()->json(['error' => 'No tienes permiso para eliminar esta propuesta'], 403);
        }

        // Contar miembros del grupo
        $groupMembersCount = $proposition->preMatch->group->users->count();

        // Si está aprobada por todos, no se puede eliminar
        if ($proposition->approved_votes >= $groupMembersCount) {
            return response()->json(['error' => 'No puedes eliminar una propuesta aprobada por todos'], 422);
        }

        $propositionId = $proposition->id;
        $userName = $proposition->user->name;
        $preMatchId = $proposition->pre_match_id;

        $proposition->delete();

        // ✨ GENERAR EVENTO: Proposición eliminada
        PreMatchEventService::propositionDeleted($preMatchId, $propositionId, $userName);

        return response()->json(['message' => 'Propuesta eliminada']);
    }

    /**
     * PUT /api/pre-matches/{id}/resolve
     * Resolver desafío (Admin): especificar perdedores y aplicar penalizaciones
     */
    public function resolvePreMatch(Request $request, PreMatch $preMatch): JsonResponse
    {
        // Authorize - only creator or admin
        if ($preMatch->created_by !== auth()->id()) {
            return response()->json(['error' => 'No tienes permiso para resolver este desafío'], 403);
        }

        $validated = $request->validate([
            'loser_ids' => 'required|array',
            'loser_ids.*' => 'exists:users,id',
            'penalty_points' => 'required|numeric|min:0',
        ]);

        $loserIds = $validated['loser_ids'];
        $penaltyPoints = $validated['penalty_points'];

        // Apply penalties to each loser
        foreach ($loserIds as $loserId) {
            // Apply penalty to loser - deduct from group_user pivot table
            if ($preMatch->penalty_type === 'POINTS') {
                $groupUser = $preMatch->group->users()
                    ->wherePivot('user_id', $loserId)
                    ->first();

                if ($groupUser) {
                    $currentPoints = $groupUser->pivot->points ?? 0;
                    $newPoints = max(0, $currentPoints - $penaltyPoints);

                    $preMatch->group->users()->updateExistingPivot($loserId, [
                        'points' => $newPoints
                    ]);
                }
            }

            // Create penalty record
            GroupPenalty::create([
                'group_id' => $preMatch->group_id,
                'user_id' => $loserId,
                'pre_match_id' => $preMatch->id,
                'penalty_type' => $preMatch->penalty_type,
                'penalty_data' => [
                    'points' => $penaltyPoints ?? 0,
                ],
                'penalty_description' => $preMatch->penalty_description ?? 'Castigo por perder el desafío',
                'is_resolved' => true,
            ]);
        }

        // Update pre-match status to completed
        $oldStatus = $preMatch->status;
        $preMatch->update(['status' => 'completed']);

        // ✨ GENERAR EVENTO: Pre-Match resuelto
        PreMatchEventService::statusChanged($preMatch, $oldStatus, 'completed');

        return response()->json([
            'message' => 'Desafío resuelto exitosamente',
            'pre_match' => $preMatch,
        ]);
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

