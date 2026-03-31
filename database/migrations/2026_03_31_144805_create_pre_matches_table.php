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
        if (Schema::hasTable('pre_matches')) {
            return; // Tabla ya existe, skip
        }
        
        Schema::create('pre_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('football_match_id')->nullable()->constrained('football_matches')->onDelete('cascade');
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // Admin que crea el desafío
            $table->enum('penalty_type', ['POINTS', 'SOCIAL', 'REVANCHA'])->default('POINTS');
            $table->unsignedInteger('penalty_points')->nullable(); // Si type=POINTS
            $table->text('penalty_description')->nullable(); // Para SOCIAL: "Pagar cena", etc
            $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])->default('active');
            $table->text('admin_notes')->nullable(); // Notas del admin
            $table->timestamps();
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_matches');
    }
};
