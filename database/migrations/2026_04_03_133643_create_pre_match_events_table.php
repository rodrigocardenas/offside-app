<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pre_match_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_match_id')
                ->constrained('pre_matches')
                ->onDelete('cascade');
            $table->string('event_type');
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Índices para queries rápidas
            $table->index('pre_match_id');
            $table->index(['processed_at', 'created_at']);
            $table->index('event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_match_events');
    }
};
