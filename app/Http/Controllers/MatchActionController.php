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
        try {
            $actions = MatchAction::where('active', true)
                ->orderByDesc('popularity')
                ->orderBy('title')
                ->get(['id', 'title', 'description', 'category', 'icon']);

            return response()->json($actions);
        } catch (\Exception $e) {
            // Si la tabla no existe aún (en desarrollo), retornar array vacío
            \Log::warning('MatchAction table not accessible: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Incrementar contador de popularidad
     */
    public function incrementPopularity(MatchAction $matchAction): JsonResponse
    {
        try {
            $matchAction->increment('popularity');
            
            return response()->json([
                'message' => 'Popularidad incrementada',
                'popularity' => $matchAction->popularity
            ]);
        } catch (\Exception $e) {
            \Log::warning('Error incrementing popularity: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error actualizando popularidad'
            ], 500);
        }
    }
}
