<?php

namespace Tests\Feature;

use App\Models\PreMatchEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreMatchEventCreationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: PreMatchEvent model funciona correctamente
     */
    public function test_pre_match_event_can_be_created()
    {
        // Crear un evento de prueba
        $event = PreMatchEvent::create([
            'pre_match_id' => 1,
            'event_type' => 'proposition.created',
            'payload' => json_encode([
                'proposition_id' => 1,
                'user_name' => 'John Doe',
                'action' => 'Gol de cabeza',
            ]),
        ]);

        // Verificar que se creó
        $this->assertNotNull($event->id);
        $this->assertEquals(1, $event->pre_match_id);
        $this->assertEquals('proposition.created', $event->event_type);
    }

    /**
     * Test: Payload JSON se crea y recupera correctamente
     */
    public function test_event_payload_casting()
    {
        $payload = [
            'proposition_id' => 123,
            'user_id' => 456,
            'action' => 'Falta dentro del área',
        ];

        $event = PreMatchEvent::create([
            'pre_match_id' => 1,
            'event_type' => 'test.event',
            'payload' => $payload,
        ]);

        $retrieved = PreMatchEvent::find($event->id);

        $this->assertIsArray($retrieved->payload);
        $this->assertEquals(123, $retrieved->payload['proposition_id']);
        $this->assertEquals(456, $retrieved->payload['user_id']);
        $this->assertEquals('Falta dentro del área', $retrieved->payload['action']);
    }

    /**
     * Test: Múltiples eventos por pre-match
     */
    public function test_multiple_events_for_same_pre_match()
    {
        $preMatchId = 42;

        PreMatchEvent::create([
            'pre_match_id' => $preMatchId,
            'event_type' => 'proposition.created',
            'payload' => json_encode(['prop_id' => 1]),
        ]);

        PreMatchEvent::create([
            'pre_match_id' => $preMatchId,
            'event_type' => 'vote.created',
            'payload' => json_encode(['voter_id' => 1]),
        ]);

        PreMatchEvent::create([
            'pre_match_id' => $preMatchId,
            'event_type' => 'status.changed',
            'payload' => json_encode(['new_status' => 'active']),
        ]);

        $events = PreMatchEvent::where('pre_match_id', $preMatchId)->get();

        $this->assertCount(3, $events);
        $this->assertEquals('proposition.created', $events[0]->event_type);
        $this->assertEquals('vote.created', $events[1]->event_type);
        $this->assertEquals('status.changed', $events[2]->event_type);
    }

    /**
     * Test: Todos los tipos de eventos soportados
     */
    public function test_all_supported_event_types()
    {
        $eventTypes = [
            'proposition.created',
            'proposition.deleted',
            'proposition.auto_approved',
            'vote.created',
            'status.changed',
            'status.pending_to_active',
            'status.resolved',
        ];

        foreach ($eventTypes as $type) {
            PreMatchEvent::create([
                'pre_match_id' => 1,
                'event_type' => $type,
                'payload' => json_encode(['test' => true]),
            ]);
        }

        $createdTypes = PreMatchEvent::where('pre_match_id', 1)
            ->pluck('event_type')
            ->toArray();

        foreach ($eventTypes as $type) {
            $this->assertContains($type, $createdTypes);
        }
    }

    /**
     * Test: processed_at puede marcarse
     */
    public function test_processed_at_timestamp()
    {
        $event = PreMatchEvent::create([
            'pre_match_id' => 1,
            'event_type' => 'test',
            'payload' => json_encode([]),
        ]);

        $this->assertNull($event->processed_at);

        $event->update(['processed_at' => now()]);

        $this->assertNotNull($event->refresh()->processed_at);
    }

    /**
     * Test: Índices están funcionando
     */
    public function test_event_indexing_performance()
    {
        // Crear varios eventos
        for ($i = 1; $i <= 10; $i++) {
            PreMatchEvent::create([
                'pre_match_id' => 1,
                'event_type' => "event.{$i}",
                'payload' => json_encode(['index' => $i]),
            ]);
        }

        // Verificar que se pueden recuperar por pre_match_id
        $eventsForMatch = PreMatchEvent::where('pre_match_id', 1)->get();
        $this->assertCount(10, $eventsForMatch);

        // Verificar que se pueden recuperar por event_type
        $createdEvents = PreMatchEvent::where('event_type', 'event.5')->get();
        $this->assertCount(1, $createdEvents);
    }
}
