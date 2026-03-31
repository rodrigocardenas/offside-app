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
        Schema::create('action_templates', function (Blueprint $table) {
            $table->id();
            $table->string('action'); // "2+ goles de cabeza", "Penal atajado", etc.
            $table->text('description')->nullable(); // "Muy raro pero posible"
            $table->decimal('probability', 3, 2); // 0.10 a 1.00 (10% a 100%)
            $table->enum('category', ['GOALS', 'CARDS', 'SCORING', 'DEFENSE', 'RARE', 'FUNNY']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_templates');
    }
};
