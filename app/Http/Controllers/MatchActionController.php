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

            // Si la tabla existe pero está vacía, retornar datos de demostración
            if ($actions->isEmpty()) {
                return response()->json($this->getDemoActions());
            }

            return response()->json($actions);
        } catch (\Exception $e) {
            // Si la tabla no existe aún (en desarrollo), retornar datos de demostración
            \Log::warning('MatchAction table not accessible: ' . $e->getMessage());
            return response()->json($this->getDemoActions());
        }
    }

    /**
     * Datos de demostración para desarrollo
     */
    private function getDemoActions(): array
    {
        return [
            // GOLES
            ['id' => 1, 'title' => 'Gol de cabeza', 'description' => 'Anotación con la cabeza', 'category' => 'goal', 'icon' => '⚽'],
            ['id' => 2, 'title' => 'Gol de chilena', 'description' => 'Gol acrobático de espaldas', 'category' => 'goal', 'icon' => '🎪'],
            ['id' => 3, 'title' => 'Gol de pie izquierdo', 'description' => 'Gol anotado con la pierna izquierda', 'category' => 'goal', 'icon' => '⚽'],
            ['id' => 4, 'title' => 'Gol de pie derecho', 'description' => 'Gol anotado con la pierna derecha', 'category' => 'goal', 'icon' => '⚽'],
            ['id' => 5, 'title' => 'Gol de penalti', 'description' => 'Anotación desde el punto de penalti', 'category' => 'goal', 'icon' => '🎯'],
            ['id' => 6, 'title' => 'Gol de tiro libre', 'description' => 'Gol directo desde tiro libre', 'category' => 'goal', 'icon' => '⚽'],
            
            // CONDICIONES
            ['id' => 7, 'title' => 'Local gana por 1 gol', 'description' => 'Equipo local gana con diferencia de 1', 'category' => 'condition', 'icon' => '🏠'],
            ['id' => 8, 'title' => 'Local gana por 2 goles', 'description' => 'Equipo local gana con diferencia de 2', 'category' => 'condition', 'icon' => '🏠'],
            ['id' => 9, 'title' => 'Local gana por 3 goles', 'description' => 'Equipo local gana con diferencia de 3', 'category' => 'condition', 'icon' => '🏠'],
            ['id' => 10, 'title' => 'Visitante gana por 1 gol', 'description' => 'Equipo visitante gana con diferencia de 1', 'category' => 'condition', 'icon' => '🚌'],
            ['id' => 11, 'title' => 'Visitante gana por 2 goles', 'description' => 'Equipo visitante gana con diferencia de 2', 'category' => 'condition', 'icon' => '🚌'],
            ['id' => 12, 'title' => 'Visitante gana por 3 goles', 'description' => 'Equipo visitante gana con diferencia de 3', 'category' => 'condition', 'icon' => '🚌'],
            ['id' => 13, 'title' => 'Empate 0-0', 'description' => 'Partido termina sin goles', 'category' => 'condition', 'icon' => '🤝'],
            ['id' => 14, 'title' => 'Empate 1-1', 'description' => 'Ambos equipos anotan 1 gol cada uno', 'category' => 'condition', 'icon' => '🤝'],
            ['id' => 15, 'title' => 'Empate 2-2', 'description' => 'Ambos equipos anotan 2 goles cada uno', 'category' => 'condition', 'icon' => '🤝'],
            ['id' => 16, 'title' => 'Más de 3 goles totales', 'description' => 'El partido tiene 4 o más goles', 'category' => 'condition', 'icon' => '🎊'],
            ['id' => 17, 'title' => 'Menos de 3 goles totales', 'description' => 'El partido tiene menos de 3 goles', 'category' => 'condition', 'icon' => '😴'],
            ['id' => 18, 'title' => 'Más de 5 goles totales', 'description' => 'Partidazo con muchos goles', 'category' => 'condition', 'icon' => '🎆'],
            
            // EVENTOS
            ['id' => 19, 'title' => 'Tarjeta roja a local', 'description' => 'Expulsión de jugador del equipo local', 'category' => 'event', 'icon' => '🔴'],
            ['id' => 20, 'title' => 'Tarjeta roja a visitante', 'description' => 'Expulsión de jugador del equipo visitante', 'category' => 'event', 'icon' => '🔴'],
            ['id' => 21, 'title' => 'Tarjeta amarilla a local', 'description' => 'Amonestación de jugador local', 'category' => 'event', 'icon' => '🟡'],
            ['id' => 22, 'title' => 'Tarjeta amarilla a visitante', 'description' => 'Amonestación de jugador visitante', 'category' => 'event', 'icon' => '🟡'],
            ['id' => 23, 'title' => 'Dos tarjetas amarillas', 'description' => 'Un jugador recibe dos amarillas y ve roja', 'category' => 'event', 'icon' => '🔴'],
            ['id' => 24, 'title' => 'Penalti pitado', 'description' => 'Al menos un penalti en el partido', 'category' => 'event', 'icon' => '🎯'],
            ['id' => 25, 'title' => 'Penalti no convertido', 'description' => 'Un penalti falla', 'category' => 'event', 'icon' => '😩'],
            ['id' => 26, 'title' => 'Gol anulado por fuera de juego', 'description' => 'Gol no validado por offside', 'category' => 'event', 'icon' => '📍'],
            ['id' => 27, 'title' => 'VAR revierte decisión', 'description' => 'El árbitro cambia su decisión tras revisar', 'category' => 'event', 'icon' => '📺'],
            ['id' => 28, 'title' => 'Gol del máximo anotador', 'description' => 'Anotación del delantero estrella', 'category' => 'event', 'icon' => '⭐'],
            ['id' => 29, 'title' => 'Gol de defensa', 'description' => 'Un defensa anota', 'category' => 'event', 'icon' => '🛡️'],
            
            // TIMING
            ['id' => 30, 'title' => 'Primer gol antes del minuto 15', 'description' => 'Gol en los primeros minutos', 'category' => 'timing', 'icon' => '⚡'],
            ['id' => 31, 'title' => 'Gol en segundo tiempo', 'description' => 'Primer gol después del minuto 45', 'category' => 'timing', 'icon' => '⌚'],
            ['id' => 32, 'title' => 'Gol en los últimos 10 minutos', 'description' => 'Gol muy al final del partido', 'category' => 'timing', 'icon' => '⏰'],
            ['id' => 33, 'title' => 'Gol del empate en último minuto', 'description' => 'Gol de la igualdad muy al final', 'category' => 'timing', 'icon' => '⏱️'],
            ['id' => 34, 'title' => 'Gol de la victoria en tiempo añadido', 'description' => 'Gol ganador en los últimos segundos', 'category' => 'timing', 'icon' => '🏁'],
        ];
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
