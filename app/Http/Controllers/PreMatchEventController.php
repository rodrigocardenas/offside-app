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
                $pingCounter = 0;

                // 1️⃣ Primero: Enviar últimos eventos recientes (últimas 20) para sincronización inicial
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
                        'is_recent' => true, // Flag para indicar que es evento reciente al conectar
                    ]) . "\n\n";
                    flush();
                }

                // 2️⃣ Enviar evento de "bienvenida" para confirmar conexión
                echo "data: " . json_encode([
                    'event' => 'sse.connected',
                    'data' => [
                        'user_id' => auth()->id(),
                        'user_name' => auth()->user()->name,
                        'pre_match_id' => $preMatch->id,
                        'last_loaded_event_id' => $lastId,
                    ],
                    'timestamp' => now()->toIso8601String(),
                ]) . "\n\n";
                flush();

                // 3️⃣ Polling principal: escuchar nuevos eventos
                while ($iteration < $maxIterations) {
                    // Fetch eventos nuevos desde la última lectura
                    $events = PreMatchEvent::where('pre_match_id', $preMatch->id)
                        ->where('id', '>', $lastId)
                        ->orderBy('id')
                        ->get();

                    foreach ($events as $event) {
                        $lastId = $event->id;

                        // Enviar evento en formato SSE
                        echo "data: " . json_encode([
                            'event' => $event->event_type,
                            'data' => $event->payload,
                            'timestamp' => $event->created_at->toIso8601String(),
                            'id' => $event->id,
                        ]) . "\n\n";

                        // Marcar como procesado
                        $event->update(['processed_at' => now()]);

                        flush();
                    }

                    // 4️⃣ Enviar ping cada 30 segundos para mantener conexión viva
                    $pingCounter++;
                    if ($pingCounter >= 30) {
                        echo ":ping\n\n";
                        flush();
                        $pingCounter = 0;
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
                'X-Accel-Buffering' => 'no', // Nginx/Apache buffering
            ]
        );
    }
}
