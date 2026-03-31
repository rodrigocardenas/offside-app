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
        Schema::create('group_penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Usuario penalizado
            $table->foreignId('pre_match_id')->constrained('pre_matches')->onDelete('cascade');
            $table->enum('penalty_type', ['POINTS', 'SOCIAL', 'REVANCHA']); // Tipo de penalización
            $table->json('penalty_data')->nullable(); // {'points': 500, 'description': '...'}
            $table->text('penalty_description')->nullable(); // "Pagar cena a...", "Hacer video...", etc
            $table->boolean('is_resolved')->default(false); // ¿Penalty completada?
            $table->text('admin_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['group_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_penalties');
    }
};
