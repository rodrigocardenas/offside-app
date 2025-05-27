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
        Schema::table('answers', function (Blueprint $table) {
            // Eliminar la columna option_id si existe
            if (Schema::hasColumn('answers', 'option_id')) {
                $table->dropForeign(['option_id']);
                $table->dropColumn('option_id');
            }

            // Agregar la columna question_option_id si no existe
            if (!Schema::hasColumn('answers', 'question_option_id')) {
                $table->foreignId('question_option_id')->constrained('question_options')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            // Eliminar la columna question_option_id si existe
            if (Schema::hasColumn('answers', 'question_option_id')) {
                $table->dropForeign(['question_option_id']);
                $table->dropColumn('question_option_id');
            }

            // Restaurar la columna option_id
            if (!Schema::hasColumn('answers', 'option_id')) {
                $table->foreignId('option_id')->constrained('question_options')->onDelete('cascade');
            }
        });
    }
};
