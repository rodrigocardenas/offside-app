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
        // Only alter if table exists
        if (Schema::hasTable('football_matches')) {
            Schema::table('football_matches', function (Blueprint $table) {
                if (!Schema::hasColumn('football_matches', 'external_id')) {
                    $table->string('external_id')->unique()->nullable();
                }
                if (!Schema::hasColumn('football_matches', 'home_team')) {
                    $table->string('home_team');
                }
                if (!Schema::hasColumn('football_matches', 'away_team')) {
                    $table->string('away_team');
                }
                if (!Schema::hasColumn('football_matches', 'date')) {
                    $table->timestamp('date');
                }
                if (!Schema::hasColumn('football_matches', 'status')) {
                    $table->string('status');
                }
                if (!Schema::hasColumn('football_matches', 'stadium')) {
                    $table->string('stadium')->nullable();
                }
                if (!Schema::hasColumn('football_matches', 'league')) {
                    $table->string('league');
                }
                if (!Schema::hasColumn('football_matches', 'score')) {
                    $table->string('score')->nullable();
                }
                if (!Schema::hasColumn('football_matches', 'events')) {
                    $table->text('events')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('football_matches')) {
            Schema::table('football_matches', function (Blueprint $table) {
                $columns = [
                    'external_id',
                    'home_team',
                    'away_team',
                    'date',
                    'status',
                    'stadium',
                    'league',
                    'score',
                    'events'
                ];
                
                foreach ($columns as $column) {
                    if (Schema::hasColumn('football_matches', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }    }
};