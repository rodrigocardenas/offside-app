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
        Schema::table('football_matches', function (Blueprint $table) {
            if (!Schema::hasColumn('football_matches', 'home_team_score')) {
                $table->integer('home_team_score')->nullable();
            }
            if (!Schema::hasColumn('football_matches', 'away_team_score')) {
                $table->integer('away_team_score')->nullable();
            }
            if (!Schema::hasColumn('football_matches', 'home_team_penalties')) {
                $table->integer('home_team_penalties')->nullable();
            }
            if (!Schema::hasColumn('football_matches', 'away_team_penalties')) {
                $table->integer('away_team_penalties')->nullable();
            }
            if (!Schema::hasColumn('football_matches', 'winner')) {
                $table->string('winner')->nullable();
            }
            if (!Schema::hasColumn('football_matches', 'matchday')) {
                $table->string('matchday')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('football_matches', function (Blueprint $table) {
            $table->dropColumn([
                'home_team_score',
                'away_team_score',
                'home_team_penalties',
                'away_team_penalties',
                'winner',
                'matchday'
            ]);
        });
    }
};
