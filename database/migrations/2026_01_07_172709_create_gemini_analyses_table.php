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
        Schema::create('gemini_analyses', function (Blueprint $table) {
            $table->id();

            // Relación con el partido
            $table->foreignId('football_match_id')
                  ->constrained()
                  ->onDelete('cascade');

            // Tipo de análisis: 'pre_match', 'live', 'post_match'
            $table->enum('analysis_type', ['pre_match', 'live', 'post_match'])
                  ->default('post_match')
                  ->index();

            // Datos del análisis en JSON
            $table->json('analysis_data')->nullable();

            // Resumen en texto plano
            $table->longText('summary')->nullable();

            // Información de grounding (fuentes citadas)
            $table->json('grounding_sources')->nullable();

            // Confiabilidad/score del análisis
            $table->decimal('confidence_score', 3, 2)->nullable();

            // Tokens utilizados
            $table->integer('tokens_used')->nullable();

            // Tiempo de procesamiento
            $table->integer('processing_time_ms')->nullable();

            // Estado del análisis
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                  ->default('pending')
                  ->index();

            // Mensaje de error si aplica
            $table->text('error_message')->nullable();

            // Intentos realizados
            $table->integer('attempt_count')->default(0);

            // Usuario que solicitó el análisis
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained()
                  ->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Índices para búsquedas frecuentes
            $table->index(['football_match_id', 'analysis_type']);
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gemini_analyses');
    }
};
