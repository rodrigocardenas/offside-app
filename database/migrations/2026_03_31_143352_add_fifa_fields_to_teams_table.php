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
            // Agregar columna fifa_code única para países FIFA (3 letras: ARG, BRA, etc)
            $table->string('fifa_code', 3)->nullable()->unique()->after('tla');

            // Confederación FIFA (CONMEBOL, UEFA, CONCACAF, AFC, CAF, OFC)
            $table->string('confederation')->nullable()->after('fifa_code');

            // Región geográfica (South America, Europe, Africa, Asia, Oceania, etc)
            $table->string('region')->nullable()->after('confederation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['fifa_code', 'confederation', 'region']);
        });
    }
};
