<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('teams')) {
            return;
        }

        Schema::table('teams', function (Blueprint $table) {
            if (!Schema::hasColumn('teams', 'api_name')) {
                $table->string('api_name')->nullable()->after('name');
            }
        });

        DB::table('teams')
            ->whereNull('api_name')
            ->update(['api_name' => DB::raw('name')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('teams')) {
            return;
        }

        Schema::table('teams', function (Blueprint $table) {
            if (Schema::hasColumn('teams', 'api_name')) {
                $table->dropColumn('api_name');
            }
        });
    }
};
