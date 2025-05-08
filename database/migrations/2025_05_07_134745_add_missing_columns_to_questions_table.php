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
        // Primero, hacer que la columna category sea nullable
        Schema::table('questions', function (Blueprint $table) {
            $table->string('category')->nullable()->change();
        });

        // Luego, agregar las nuevas columnas
        Schema::table('questions', function (Blueprint $table) {
            // $table->foreignId('match_id')->nullable()->constrained('football_matches')->onDelete('cascade');
            // $table->boolean('is_featured')->default(false);
            // $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            // $table->foreignId('template_question_id')->nullable()->constrained('template_questions')->onDelete('set null');
            // $table->foreignId('competition_id')->nullable()->constrained('competitions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['match_id']);
            $table->dropColumn('match_id');
            $table->dropColumn('is_featured');
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->dropForeign(['template_question_id']);
            $table->dropColumn('template_question_id');
            $table->dropForeign(['competition_id']);
            $table->dropColumn('competition_id');
            
            // Revertir el cambio en la columna category
            $table->string('category')->nullable(false)->change();
        });
    }
};
