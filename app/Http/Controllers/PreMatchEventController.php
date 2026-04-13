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
            abort(403, 'No tienes acceso a este pre-match');
        }

        // Headers para SSE
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Deshabilitar buffering
        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', 1);
        }
        ini_set('output_buffering', 0);
        ini_set('implicit_flush', 1);

        $lastId = 0;
        $iteration = 0;

        // Evento de bienvenida
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

        \Log::info('SSE: Evento connected enviado');

        // Loop de polling
        while ($iteration < 300) {
            $events = PreMatchEvent::where('pre_match_id', $preMatch->id)
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->get();

            if ($events->count() > 0) {
                \Log::info('SSE: Encontrados ' . $events->count() . ' eventos');
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

            sleep(1);
            $iteration++;

            if (connection_aborted()) {
                \Log::info('SSE: Conexión abortada por cliente');
                break;
            }
        }

        \Log::info('SSE: Stream terminado después de ' . $iteration . ' iteraciones');
        exit;
    }
}
