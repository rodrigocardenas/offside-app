<?php

namespace App\Http\Controllers;

use App\Models\MatchAction;
use Illuminate\Http\JsonResponse;

class MatchActionController extends Controller
{
    /**
     * Obtener las acciones de partido activas, ordenadas por popularidad
     */
    public function index(): JsonResponse
    {
        $actions = MatchAction::where('active', true)
            ->orderByDesc('popularity')
            ->orderBy('title')
            ->get(['id', 'title', 'description', 'category', 'icon']);

        return response()->json($actions);
    }

    /**
     * Incrementar contador de popularidad
     */
    public function incrementPopularity(MatchAction $matchAction): JsonResponse
    {
        $matchAction->increment('popularity');
        
        return response()->json([
            'message' => 'Popularidad incrementada',
            'popularity' => $matchAction->popularity
        ]);
    }
}
