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
        Schema::create('football_matches', function (Blueprint $table) {
            $table->id();
            
            // Identificadores externos
            $table->string('external_id')->unique()->nullable();
            
            // Equipos
            $table->string('home_team')->nullable();
            $table->string('away_team')->nullable();
            $table->foreignId('home_team_id')->nullable()->constrained('teams')->onDelete('set null');
            $table->foreignId('away_team_id')->nullable()->constrained('teams')->onDelete('set null');
            
            // Competencia
            $table->foreignId('competition_id')->nullable()->constrained('competitions')->onDelete('set null');
            $table->string('league')->nullable();
            
            // Información del partido
            $table->dateTime('match_date')->nullable()->index();
            $table->dateTime('date')->nullable();
            $table->string('status')->default('SCHEDULED'); // SCHEDULED, LIVE, FINISHED, POSTPONED
            $table->string('matchday')->nullable();
            $table->string('stage')->nullable();
            $table->string('group')->nullable();
            
            // Resultado
            $table->integer('home_team_score')->nullable();
            $table->integer('away_team_score')->nullable();
            $table->integer('home_team_penalties')->nullable();
            $table->integer('away_team_penalties')->nullable();
            $table->string('winner')->nullable(); // HOME, AWAY, DRAW
            
            // Información adicional
            $table->foreignId('stadium_id')->nullable()->constrained('stadiums')->onDelete('set null');
            $table->integer('season')->nullable();
            $table->string('duration')->nullable();
            $table->string('referee')->nullable();
            
            // Datos adicionales (JSON)
            $table->json('events')->nullable();
            $table->json('statistics')->nullable();
            $table->text('score')->nullable();
            
            // Featured
            $table->boolean('is_featured')->default(false);
            
            // Verificación
            $table->dateTime('last_verification_attempt_at')->nullable();
            $table->integer('verification_priority')->default(0);
            
            $table->timestamps();
            
            // Índices
            $table->index(['competition_id', 'match_date']);
            $table->index(['status', 'match_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('football_matches');
    }
};
