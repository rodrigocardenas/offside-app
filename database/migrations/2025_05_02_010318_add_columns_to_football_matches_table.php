<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('football_matches', function (Blueprint $table) {
            // Add foreign key for home_team_id
            $table->unsignedBigInteger('home_team_id')->nullable()->after('id');
            $table->foreign('home_team_id')
                ->references('id')
                ->on('teams')
                ->onDelete('set null');

            // Add foreign key for away_team_id
            $table->unsignedBigInteger('away_team_id')->nullable()->after('home_team_id');
            $table->foreign('away_team_id')
                ->references('id')
                ->on('teams')
                ->onDelete('set null');

            // Add foreign key for stadium_id
            $table->unsignedBigInteger('stadium_id')->nullable()->after('away_team_id');
            $table->foreign('stadium_id')
                ->references('id')
                ->on('stadiums')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('football_matches', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['home_team_id']);
            $table->dropForeign(['away_team_id']);
            $table->dropForeign(['stadium_id']);
            
            // Drop columns
            $table->dropColumn(['home_team_id', 'away_team_id', 'stadium_id']);
        });
    }
};
