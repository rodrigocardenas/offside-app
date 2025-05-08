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
            $table->string('external_id')->unique()->nullable();
            $table->string('home_team');
            $table->string('away_team');
            $table->timestamp('date');
            $table->string('status');
            $table->string('stadium')->nullable();
            $table->string('league');
            $table->string('score')->nullable();
            $table->text('events')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('football_matches', function (Blueprint $table) {
            $table->dropColumn([
                'external_id',
                'home_team',
                'away_team',
                'date',
                'status',
                'stadium',
                'league',
                'score',
                'events'
            ]);
        });
    }
};
