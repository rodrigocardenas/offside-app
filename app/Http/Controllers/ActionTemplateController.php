<?php

namespace App\Http\Controllers;

use App\Models\ActionTemplate;
use Illuminate\Http\JsonResponse;

class ActionTemplateController extends Controller
{
    /**
     * Obtener todas las acciones plantilla
     * GET /api/action-templates
     */
    public function index(): JsonResponse
    {
        $templates = ActionTemplate::all();

        return response()->json([
            'data' => $templates,
            'count' => $templates->count(),
        ]);
    }

    /**
     * Obtener una acción plantilla aleatoria con probabilidad
     * GET /api/action-templates/random
     */
    public function random(): JsonResponse
    {
        // Seleccionar una acción al azar ponderada por probabilidad
        $templates = ActionTemplate::all();

        if ($templates->isEmpty()) {
            return response()->json(
                ['message' => 'No action templates available'],
                404
            );
        }

        // Algoritmo simple: seleccionar basado en probabilidad acumulativa
        $rand = rand(0, 100) / 100;
        $accumulated = 0;

        foreach ($templates as $template) {
            $accumulated += $template->probability;
            if ($rand <= $accumulated) {
                return response()->json([
                    'data' => $template,
                    'message' => 'Random action suggestion',
                ]);
            }
        }

        // Fallback: retornar la última si no se seleccionó ninguna
        return response()->json([
            'data' => $templates->last(),
            'message' => 'Random action suggestion (fallback)',
        ]);
    }

    /**
     * Obtener acciones por categoría
     * GET /api/action-templates/category/{category}
     */
    public function byCategory(string $category): JsonResponse
    {
        $templates = ActionTemplate::where('category', $category)->get();

        return response()->json([
            'data' => $templates,
            'category' => $category,
            'count' => $templates->count(),
        ]);
    }
}
