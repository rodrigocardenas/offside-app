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
        \Log::info('🔴 SSE: Iniciando stream', ['pre_match_id' => $preMatch->id, 'user_id' => auth()->id()]);

        // Validar acceso
        if (!auth()->user()->groups()->where('groups.id', $preMatch->group_id)->exists()) {
            abort(403, 'No tienes acceso a este pre-match');
        }

        // ⚠️  CRITICAL: Limpiar TODOS los buffers de salida abiertos
        while (@ob_get_level()) {
            @ob_end_clean();
        }

        // ⚠️  Headers SSE ANTES de cualquier output
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        header('X-Content-Type-Options: nosniff');

        // Disable ALL compression/buffering at PHP level
        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', 1);
        }
        ini_set('output_buffering', 0);
        ini_set('implicit_flush', 1);
        ini_set('zlib.output_compression', 0);
        set_time_limit(0);

        \Log::info('✅ SSE: Headers enviados y buffering deshabilitado');

        // 1️⃣  Send sse.connected event
        $connected = [
            'event' => 'sse.connected',
            'user' => [
                'id' => auth()->id(),
                'name' => auth()->user()?->name,
            ],
            'pre_match_id' => $preMatch->id,
            'timestamp' => now()->toIso8601String(),
        ];
        
        echo "data: " . json_encode($connected) . "\n\n";
        @flush();
        \Log::info('🟢 SSE: sse.connected enviado al cliente');

        // 2️⃣  Stream existing events (marked as historical, silencioso)
        $lastEventId = 0;
        $events = PreMatchEvent::where('pre_match_id', $preMatch->id)
            ->orderBy('created_at', 'asc')
            ->get();

        \Log::info('📦 SSE: Leyendo ' . count($events) . ' eventos históricos');

        foreach ($events as $event) {
            $lastEventId = $event->id;
            $payload = [
                'event' => $event->event_type,
                'data' => $event->payload ?? [],
                'id' => $event->id,
                'timestamp' => $event->created_at->toIso8601String(),
                'is_historical' => true,  // 🔑 Flag para evitar toasts
            ];

            echo "data: " . json_encode($payload) . "\n\n";
            @flush();
            sleep(1);
        }

        // 3️⃣  LOOP CONTINUO: Esperar nuevos eventos en tiempo real (polling)
        $iteration = 0;
        $maxIterations = 600; // 10 minutos si sleep es 1 segundo

        while ($iteration < $maxIterations) {
            // Buscar eventos nuevos desde el último que enviamos
            $newEvents = PreMatchEvent::where('pre_match_id', $preMatch->id)
                ->where('id', '>', $lastEventId)
                ->orderBy('created_at', 'asc')
                ->get();

            if ($newEvents->count() > 0) {
                \Log::info('🆕 SSE: ' . $newEvents->count() . ' eventos nuevos encontrados');
                
                foreach ($newEvents as $event) {
                    $lastEventId = $event->id;
                    $payload = [
                        'event' => $event->event_type,
                        'data' => $event->payload ?? [],
                        'id' => $event->id,
                        'timestamp' => $event->created_at->toIso8601String(),
                        'is_historical' => false,  // ✅ Eventos nuevos = mostrar toasts
                    ];

                    echo "data: " . json_encode($payload) . "\n\n";
                    @flush();
                }
            }

            // Heartbeat para mantener conexión viva
            echo ": heartbeat\n\n";
            @flush();

            sleep(1);
            $iteration++;

            // Verificar si el cliente se desconectó
            if (connection_aborted()) {
                \Log::info('🔌 SSE: Cliente desconectado');
                break;
            }
        }

        \Log::info('✅ SSE: Stream terminado después de ' . $iteration . ' iteraciones');
        exit(0);
    }

    /**
     * Polling endpoint - Mas simple y confiable que SSE
     * GET /api/pre-matches/{preMatch}/events-poll?last_id=0
     */
    public function poll(PreMatch $preMatch)
    {
        // Validar acceso
        if (!auth()->user()->groups()->where('groups.id', $preMatch->group_id)->exists()) {
            abort(403, 'No tienes acceso a este pre-match');
        }

        $lastId = request()->query('last_id', 0);
        
        // Buscar eventos nuevos desde el último que el cliente recibió
        $events = PreMatchEvent::where('pre_match_id', $preMatch->id)
            ->where('id', '>', $lastId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($event) {
                return [
                    'event' => $event->event_type,
                    'data' => $event->payload ?? [],
                    'id' => $event->id,
                    'timestamp' => $event->created_at->toIso8601String(),
                    'is_historical' => false,
                ];
            });

        return response()->json([
            'events' => $events,
            'last_id' => $events->isEmpty() ? $lastId : $events->last()['id'],
        ]);
    }
}
