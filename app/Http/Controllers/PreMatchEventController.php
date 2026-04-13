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
                $lastId = 0;
                $maxIterations = 300; // 5 minutos con poll de 1 segundo
                $iteration = 0;

                while ($iteration < $maxIterations) {
                    // Fetch eventos nuevos desde la última lectura
                    $events = PreMatchEvent::where('pre_match_id', $preMatch->id)
                        ->where('id', '>', $lastId)
                        ->orderBy('id')
                        ->get();

                    foreach ($events as $event) {
                        $lastId = $event->id;

                        // Enviar evento en formato SSE
                        // ⚠️ Decodificar payload JSON para que el cliente lo reciba como objeto, no string
                        echo "data: " . json_encode([
                            'event' => $event->event_type,
                            'data' => json_decode($event->payload, true),  // Decodificar aquí
                            'timestamp' => $event->created_at->toIso8601String(),
                            'id' => $event->id,
                        ]) . "\n\n";

                        // Marcar como procesado
                        $event->update(['processed_at' => now()]);

                        flush();
                    }

                    // Check cada 1 segundo
                    sleep(1);
                    $iteration++;

                    // Verificar si la conexión fue cerrada por el cliente
                    if (connection_aborted()) {
                        break;
                    }
                }
            },
            200,
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no', // Nginx
            ]
        );
    }
}
