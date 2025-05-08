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
        Schema::table('teams', function (Blueprint $table) {
            if (!Schema::hasColumn('teams', 'external_id')) {
                $table->string('external_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('teams', 'country')) {
                $table->string('country')->nullable()->after('short_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            if (Schema::hasColumn('teams', 'external_id')) {
                $table->dropColumn('external_id');
            }
            if (Schema::hasColumn('teams', 'country')) {
                $table->dropColumn('country');
            }
        });
    }
};
