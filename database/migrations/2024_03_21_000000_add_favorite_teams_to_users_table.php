<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('favorite_competition_id')->nullable()->constrained('competitions')->nullOnDelete();
            $table->foreignId('favorite_club_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('favorite_national_team_id')->nullable()->constrained('teams')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['favorite_competition_id']);
            $table->dropForeign(['favorite_club_id']);
            $table->dropForeign(['favorite_national_team_id']);
            $table->dropColumn([
                'favorite_competition_id',
                'favorite_club_id',
                'favorite_national_team_id'
            ]);
        });
    }
};
