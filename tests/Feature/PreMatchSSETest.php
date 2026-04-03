<?php

namespace Tests\Feature;

use App\Models\PreMatchEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreMatchSSETest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Crear evento y verificar que se almacena en la BD
     */
    public function test_pre_match_event_creation()
    {
        $event = PreMatchEvent::create([
            'pre_match_id' => 1,
            'event_type' => 'proposition.created',
            'payload' => json_encode([
                'proposition_id' => 1,
                'user_name' => 'Test User',
                'action' => 'Gol de cabeza',
            ]),
        ]);

        $this->assertDatabaseHas('pre_match_events', [
            'id' => $event->id,
            'pre_match_id' => 1,
            'event_type' => 'proposition.created',
        ]);
    }

    /**
     * Test: Verificar que los eventos se marcan como procesados
     */
    public function test_pre_match_event_processed_at_update()
    {
        $event = PreMatchEvent::create([
            'pre_match_id' => 1,
            'event_type' => 'vote.created',
            'payload' => json_encode(['voter_id' => 1]),
        ]);

        $this->assertNull($event->processed_at);

        $event->update(['processed_at' => now()]);

        $this->assertNotNull($event->refresh()->processed_at);
    }

    /**
     * Test: JSON casting funciona correctamente
     */
    public function test_event_payload_json_casting()
    {
        $payload = [
            'user_id' => 1,
            'action' => 'Test Action',
            'nested' => ['key' => 'value'],
        ];

        $event = PreMatchEvent::create([
            'pre_match_id' => 1,
            'event_type' => 'test.event',
            'payload' => $payload,
        ]);

        $this->assertIsArray($event->payload);
        $this->assertEquals($payload, $event->payload);
        $this->assertEquals('Test Action', $event->payload['action']);
    }

    /**
     * Test: Múltiples eventos se recuperan en orden
     */
    public function test_multiple_events_retrieved_in_order()
    {
        for ($i = 1; $i <= 5; $i++) {
            PreMatchEvent::create([
                'pre_match_id' => 1,
                'event_type' => "event.type.{$i}",
                'payload' => json_encode(['index' => $i]),
            ]);
        }

        $retrieved = PreMatchEvent::where('pre_match_id', 1)
            ->orderBy('id')
            ->pluck('event_type')
            ->toArray();

        $this->assertCount(5, $retrieved);
        for ($i = 1; $i <= 5; $i++) {
            $this->assertEquals("event.type.{$i}", $retrieved[$i - 1]);
        }
    }

    /**
     * Test: Verificar tabla en la BD
     */
    public function test_pre_match_events_table_exists()
    {
        $this->assertTrue(
            \DB::connection()->getSchemaBuilder()->hasTable('pre_match_events'),
            'Tabla pre_match_events debe existir'
        );
    }

    /**
     * Test: Verificar columnas de la tabla
     */
    public function test_pre_match_events_has_required_columns()
    {
        $columns = \DB::connection()->getSchemaBuilder()->getColumnListing('pre_match_events');

        $requiredColumns = ['id', 'pre_match_id', 'event_type', 'payload', 'processed_at', 'created_at', 'updated_at'];

        foreach ($requiredColumns as $column) {
            $this->assertContains($column, $columns, "Columna '{$column}' debe existir en tabla pre_match_events");
        }
    }
}

