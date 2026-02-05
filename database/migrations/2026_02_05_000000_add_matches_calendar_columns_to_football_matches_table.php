<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega las columnas faltantes necesarias para el MatchesCalendarService
     */
    public function up(): void
    {
        Schema::table('football_matches', function (Blueprint $table) {
            // Agregar columnas que faltan para el service de calendario
            if (!Schema::hasColumn('football_matches', 'match_date')) {
                $table->dateTime('match_date')->nullable()->index();
            }

            if (!Schema::hasColumn('football_matches', 'competition_id')) {
                $table->foreignId('competition_id')->nullable()->constrained('competitions')->onDelete('set null');
            }

            if (!Schema::hasColumn('football_matches', 'home_team_id')) {
                $table->foreignId('home_team_id')->nullable()->constrained('teams')->onDelete('set null');
            }

            if (!Schema::hasColumn('football_matches', 'away_team_id')) {
                $table->foreignId('away_team_id')->nullable()->constrained('teams')->onDelete('set null');
            }

            if (!Schema::hasColumn('football_matches', 'stadium_id')) {
                $table->foreignId('stadium_id')->nullable()->constrained('stadiums')->onDelete('set null');
            }

            if (!Schema::hasColumn('football_matches', 'season')) {
                $table->integer('season')->nullable();
            }

            if (!Schema::hasColumn('football_matches', 'stage')) {
                $table->string('stage')->nullable();
            }

            if (!Schema::hasColumn('football_matches', 'group')) {
                $table->string('group')->nullable();
            }

            if (!Schema::hasColumn('football_matches', 'duration')) {
                $table->string('duration')->nullable();
            }

            if (!Schema::hasColumn('football_matches', 'referee')) {
                $table->string('referee')->nullable();
            }

            if (!Schema::hasColumn('football_matches', 'statistics')) {
                $table->json('statistics')->nullable();
            }

            if (!Schema::hasColumn('football_matches', 'is_featured')) {
                $table->boolean('is_featured')->default(false);
            }

            if (!Schema::hasColumn('football_matches', 'last_verification_attempt_at')) {
                $table->dateTime('last_verification_attempt_at')->nullable();
            }

            if (!Schema::hasColumn('football_matches', 'verification_priority')) {
                $table->integer('verification_priority')->default(0);
            }

            // Agregar índices si no existen
            if (!Schema::hasIndex('football_matches', 'football_matches_competition_id_match_date_index')) {
                $table->index(['competition_id', 'match_date']);
            }

            if (!Schema::hasIndex('football_matches', 'football_matches_status_match_date_index')) {
                $table->index(['status', 'match_date']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('football_matches', function (Blueprint $table) {
            // Revertir solo si las columnas existen
            $columns = Schema::getColumnListing('football_matches');

            if (in_array('match_date', $columns)) {
                $table->dropIndex(['match_date']);
                $table->dropColumn('match_date');
            }

            if (in_array('competition_id', $columns)) {
                $table->dropForeign(['competition_id']);
                $table->dropColumn('competition_id');
            }

            if (in_array('home_team_id', $columns)) {
                $table->dropForeign(['home_team_id']);
                $table->dropColumn('home_team_id');
            }

            if (in_array('away_team_id', $columns)) {
                $table->dropForeign(['away_team_id']);
                $table->dropColumn('away_team_id');
            }

            if (in_array('stadium_id', $columns)) {
                $table->dropForeign(['stadium_id']);
                $table->dropColumn('stadium_id');
            }

            if (in_array('season', $columns)) {
                $table->dropColumn('season');
            }

            if (in_array('stage', $columns)) {
                $table->dropColumn('stage');
            }

            if (in_array('group', $columns)) {
                $table->dropColumn('group');
            }

            if (in_array('duration', $columns)) {
                $table->dropColumn('duration');
            }

            if (in_array('referee', $columns)) {
                $table->dropColumn('referee');
            }

            if (in_array('statistics', $columns)) {
                $table->dropColumn('statistics');
            }

            if (in_array('is_featured', $columns)) {
                $table->dropColumn('is_featured');
            }

            if (in_array('last_verification_attempt_at', $columns)) {
                $table->dropColumn('last_verification_attempt_at');
            }

            if (in_array('verification_priority', $columns)) {
                $table->dropColumn('verification_priority');
            }
        });
    }
};
