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
        Schema::create('pre_match_resolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_match_id')->constrained('pre_matches')->onDelete('cascade');
            $table->foreignId('winning_pre_match_proposition_id')->nullable()->constrained('pre_match_propositions')->onDelete('set null');
            $table->boolean('was_fulfilled')->default(false); // ¿La acción se cumplió?
            $table->boolean('admin_verified')->default(false); // ¿Admin verificó resultado?
            $table->text('admin_evidence')->nullable(); // Minuto, evidencia video, etc
            $table->text('admin_notes')->nullable(); // Notas administrativas
            $table->timestamp('resolved_at')->nullable(); // Cuándo se resolvió
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_match_resolutions');
    }
};
