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
        Schema::table('gemini_analyses', function (Blueprint $table) {
            if (!Schema::hasColumn('gemini_analyses', 'football_match_id')) {
                $table->foreignId('football_match_id')
                      ->constrained()
                      ->onDelete('cascade')
                      ->after('id');

                // Agregar índice
                $table->index(['football_match_id', 'analysis_type']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gemini_analyses', function (Blueprint $table) {
            if (Schema::hasColumn('gemini_analyses', 'football_match_id')) {
                $table->dropForeign(['football_match_id']);
                $table->dropIndex(['football_match_id', 'analysis_type']);
                $table->dropColumn('football_match_id');
            }
        });
    }
};
