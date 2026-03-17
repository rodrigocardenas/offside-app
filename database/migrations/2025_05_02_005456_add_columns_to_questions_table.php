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
        Schema::table('questions', function (Blueprint $table) {
            // Only add foreign key if football_matches table exists
            if (Schema::hasTable('football_matches') && !Schema::hasColumn('questions', 'football_match_id')) {
                $table->foreignId('football_match_id')->nullable()->constrained('football_matches')->onDelete('set null');
            }
            if (!Schema::hasColumn('questions', 'result_verified_at')) {
                $table->timestamp('result_verified_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'football_match_id')) {
                $table->dropForeign(['football_match_id']);
                $table->dropColumn('football_match_id');
            }
            if (Schema::hasColumn('questions', 'result_verified_at')) {
                $table->dropColumn('result_verified_at');
            }        });
    }
};