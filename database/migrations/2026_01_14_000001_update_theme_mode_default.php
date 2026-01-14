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
        if (Schema::hasColumn('users', 'theme_mode')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('theme_mode')->default('light')->change();
            });

            DB::table('users')
                ->whereNull('theme_mode')
                ->orWhere('theme_mode', 'auto')
                ->update(['theme_mode' => 'light']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'theme_mode')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('theme_mode')->default('auto')->change();
            });
        }
    }
};
