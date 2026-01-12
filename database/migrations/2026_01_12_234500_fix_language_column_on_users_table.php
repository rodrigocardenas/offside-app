<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Si la columna no existe, la agregamos
        if (!Schema::hasColumn('users', 'language')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('language')->default('es')->nullable()->after('theme_mode');
            });
        }

        // Si la columna existe pero tiene datos inválidos, los limpiamos
        DB::statement("UPDATE users SET language = 'es' WHERE language IS NULL OR language = 'NULL' OR language = ''");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'language')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('language');
            });
        }
    }
};
