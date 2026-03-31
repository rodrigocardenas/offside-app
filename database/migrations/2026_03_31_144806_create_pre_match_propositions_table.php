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
        Schema::create('pre_match_propositions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_match_id')->constrained('pre_matches')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Usuario que propone
            $table->string('action'); // "Gol de cabeza", etc (puede venir de ActionTemplate)
            $table->text('description'); // Detalles específicos de la propuesta
            $table->enum('validation_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->decimal('approval_percentage', 5, 2)->nullable(); // % de votos aprobados (0-100)
            $table->unsignedInteger('votes_count')->default(0); // Total de votos
            $table->unsignedInteger('approved_votes')->default(0); // Votos aprobados
            $table->timestamps();
            $table->index('pre_match_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_match_propositions');
    }
};
