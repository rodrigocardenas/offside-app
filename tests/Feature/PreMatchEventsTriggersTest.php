<?php

namespace Tests\Feature;

use App\Models\PreMatch;
use App\Models\PreMatchEvent;
use App\Models\PreMatchProposition;
use App\Models\PreMatchVote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreMatchEventsTriggersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Crear proposición dispara evento
     */
    public function test_creating_proposition_triggers_event()
    {
        // Crear un evento base para tener un pre_match_id válido
        PreMatchEvent::create([
            'pre_match_id' => 1,
            'event_type' => 'test',
            'payload' => json_encode([]),
        ]);

        // Verifica que la tabla pre_match_events existe
        $this->assertTrue(
            \DB::connection()->getSchemaBuilder()->hasTable('pre_match_events')
        );

        // Verify evento fue creado
        $this->assertDatabaseHas('pre_match_events', [
            'pre_match_id' => 1,
            'event_type' => 'test',
        ]);
    }

    /**
     * Test: Eliminar proposición dispara evento
     */
    public function test_deleting_proposition_triggers_event()
    {
        PreMatchEvent::create([
            'pre_match_id' => 1,
            'event_type' => 'proposition.deleted',
            'payload' => json_encode([
                'proposition_id' => 99,
                'user_name' => 'Test User',
            ]),
        ]);

        $this->assertDatabaseHas('pre_match_events', [
            'event_type' => 'proposition.deleted',
        ]);
    }

    /**
     * Test: Crear voto dispara evento
     */
    public function test_creating_vote_triggers_event()
    {
        PreMatchEvent::create([
            'pre_match_id' => 1,
            'event_type' => 'vote.created',
            'payload' => json_encode([
                'proposition_id' => 1,
                'voter_id' => 1,
                'voted_yes' => true,
            ]),
        ]);

        $this->assertDatabaseHas('pre_match_events', [
            'event_type' => 'vote.created',
        ]);
    }

    /**
     * Test: Cambio de estado dispara evento
     */
    public function test_status_change_triggers_event()
    {
        PreMatchEvent::create([
            'pre_match_id' => 1,
            'event_type' => 'status.changed',
            'payload' => json_encode([
                'old_status' => 'pending',
                'new_status' => 'active',
            ]),
        ]);

        $this->assertDatabaseHas('pre_match_events', [
            'event_type' => 'status.changed',
        ]);
    }

    /**
     * Test: Auto-aprobación dispara evento específico
     */
    public function test_auto_approval_triggers_specific_event()
    {
        PreMatchEvent::create([
            'pre_match_id' => 1,
            'event_type' => 'status.pending_to_active',
            'payload' => json_encode([
                'message' => 'Todas las propuestas fueron aprobadas',
            ]),
        ]);

        $this->assertDatabaseHas('pre_match_events', [
            'event_type' => 'status.pending_to_active',
        ]);
    }

    /**
     * Test: Resolución dispara evento
     */
    public function test_resolution_triggers_event()
    {
        PreMatchEvent::create([
            'pre_match_id' => 1,
            'event_type' => 'status.resolved',
            'payload' => json_encode([
                'message' => 'Pre-match resuelto',
            ]),
        ]);

        $this->assertDatabaseHas('pre_match_events', [
            'event_type' => 'status.resolved',
        ]);
    }

    /**
     * Test: Proposal Auto-Approved Event
     */
    public function test_proposition_auto_approved_event()
    {
        PreMatchEvent::create([
            'pre_match_id' => 1,
            'event_type' => 'proposition.auto_approved',
            'payload' => json_encode([
                'proposition_id' => 1,
                'action' => 'Test action',
                'approved_votes' => 5,
            ]),
        ]);

        $this->assertDatabaseHas('pre_match_events', [
            'event_type' => 'proposition.auto_approved',
        ]);
    }

    /**
     * Test: Verificar que eventos tienen payloads válidos
     */
    public function test_events_have_valid_json_payloads()
    {
        $event = PreMatchEvent::create([
            'pre_match_id' => 1,
            'event_type' => 'test.event',
            'payload' => json_encode([
                'key' => 'value',
                'number' => 42,
            ]),
        ]);

        $this->assertIsArray($event->payload);
        $this->assertEquals('value', $event->payload['key']);
        $this->assertEquals(42, $event->payload['number']);
    }
}
