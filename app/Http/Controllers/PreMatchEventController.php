<?php

namespace App\Http\Controllers;

use App\Models\PreMatch;
use App\Models\PreMatchEvent;
use Illuminate\Http\Request;

class PreMatchEventController extends Controller
{
    /**
     * Stream eventos SSE para un pre-match
     * GET /api/pre-matches/{preMatch}/events
     */
    public function stream(PreMatch $preMatch)
    {
        // Log para debuguear
        \Log::info('SSE Stream iniciado', [
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name ?? 'No autenticado',
            'pre_match_id' => $preMatch->id,
        ]);

        // Validar que el usuario pertenece al grupo
        if (!auth()->user()->groups()->where('groups.id', $preMatch->group_id)->exists()) {
            \Log::warning('SSE: Usuario sin acceso al grupo', [
                'user_id' => auth()->id(),
                'group_id' => $preMatch->group_id,
            ]);
            abort(403, 'No tienes acceso a este pre-match');
        }

        // Usar streaming simple sin callback para evitar problemas con middleware
        $response = response()->stream(null, 200, [
            'Content-Type' => 'text/event-stream; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);

        // Obtener el callback para no usar closure
        $response->setCallback(function () use ($preMatch) {
            try {
                // Limpiar buffers
                if (ob_get_level()) {
                    ob_end_clean();
                }

                $lastId = 0;
                $iteration = 0;

                // Enviar evento de bienvenida inmediatamente
                echo "data: " . json_encode([
                    'event' => 'sse.connected',
                    'data' => [
                        'user_id' => auth()->id(),
                        'user_name' => auth()->user()->name,
                        'pre_match_id' => $preMatch->id,
                        'timestamp' => now()->toIso8601String(),
                    ],
                    'timestamp' => now()->toIso8601String(),
                ]) . "\n\n";
                flush();
                ob_flush();

                // Loop de polling
                while ($iteration < 300) {
                    $events = PreMatchEvent::where('pre_match_id', $preMatch->id)
                        ->where('id', '>', $lastId)
                        ->orderBy('id')
                        ->get();

                    foreach ($events as $event) {
                        $lastId = $event->id;
                        echo "data: " . json_encode([
                            'event' => $event->event_type,
                            'data' => $event->payload,
                            'timestamp' => $event->created_at->toIso8601String(),
                            'id' => $event->id,
                        ]) . "\n\n";
                        $event->update(['processed_at' => now()]);
                        flush();
                    }

                    sleep(1);
                    $iteration++;

                    if (connection_aborted()) {
                        break;
                    }
                }
            } catch (\Exception $e) {
                \Log::error('SSE Stream error', ['error' => $e->getMessage()]);
                echo "data: " . json_encode(['event' => 'error', 'data' => $e->getMessage()]) . "\n\n";
            }
        });

        return $response;
    }
}
