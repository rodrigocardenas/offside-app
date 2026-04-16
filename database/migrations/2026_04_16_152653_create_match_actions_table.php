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
        Schema::create('match_actions', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Por ejemplo: "Gol de chilena"
            $table->text('description')->nullable(); // Descripción más detallada
            $table->string('category'); // Por ejemplo: 'goal', 'event', 'condition'
            $table->string('icon')->nullable(); // Emoji o icono: ⚽, 🟠, etc
            $table->integer('popularity')->default(0); // Contador de uso
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            // Índices para búsqueda
            $table->index('category');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_actions');
    }
};
