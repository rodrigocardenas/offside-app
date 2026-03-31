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
        Schema::create('pre_match_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_match_proposition_id')->constrained('pre_match_propositions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Usuario que vota
            $table->boolean('approved')->default(true); // true = aprobado, false = rechazado
            $table->timestamps();
            // Un usuario solo puede votar 1 vez por proposición
            $table->unique(['pre_match_proposition_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_match_votes');
    }
};
