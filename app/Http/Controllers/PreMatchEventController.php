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
        // Validar que el usuario pertenece al grupo
        if (!auth()->user()->groups()->where('groups.id', $preMatch->group_id)->exists()) {
            abort(403, 'No tienes acceso a este pre-match');
        }

        return response()->stream(
            function () use ($preMatch) {
                // Limpiar output buffer si existe
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }

                $lastId = 0;
                $maxIterations = 300;
                $iteration = 0;
                $pingCounter = 0;

                // 1️⃣ Cargar últimos eventos
                $recentEvents = PreMatchEvent::where('pre_match_id', $preMatch->id)
                    ->latest('id')
                    ->limit(20)
                    ->orderBy('id')
                    ->get();

                foreach ($recentEvents as $event) {
                    $lastId = max($lastId, $event->id);
                    echo "data: " . json_encode([
                        'event' => $event->event_type,
                        'data' => $event->payload,
                        'timestamp' => $event->created_at->toIso8601String(),
                        'id' => $event->id,
                        'is_recent' => true,
                    ]) . "\n\n";
                    flush();
                }

                // 2️⃣ Evento de conexión confirmada
                echo "data: " . json_encode([
                    'event' => 'sse.connected',
                    'data' => [
                        'user_id' => auth()->id(),
                        'user_name' => auth()->user()->name,
                        'pre_match_id' => $preMatch->id,
                        'last_loaded_event_id' => $lastId,
                        'connected_at' => now()->toIso8601String(),
                    ],
                    'timestamp' => now()->toIso8601String(),
                ]) . "\n\n";
                flush();

                // 3️⃣ Loop principal
                while ($iteration < $maxIterations) {
                    // Buscar eventos nuevos
                    $events = PreMatchEvent::where('pre_match_id', $preMatch->id)
                        ->where('id', '>', $lastId)
                        ->orderBy('id')
                        ->get();

                    if ($events->count() > 0) {
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
                    }

                    // Ping cada 30 segundos
                    $pingCounter++;
                    if ($pingCounter >= 30) {
                        echo ":ping\n\n";
                        flush();
                        $pingCounter = 0;
                    }

                    sleep(1);
                    $iteration++;

                    if (connection_aborted()) {
                        break;
                    }
                }
            },
            200,
            [
                'Content-Type' => 'text/event-stream; charset=utf-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]
        );
    }
}
