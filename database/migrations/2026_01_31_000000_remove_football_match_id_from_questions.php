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
            // Drop the duplicate column if it exists
            if (Schema::hasColumn('questions', 'football_match_id')) {
                $table->dropColumn('football_match_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('football_match_id')->nullable()->after('match_id')->constrained('football_matches')->nullOnDelete();
        });
    }
};
