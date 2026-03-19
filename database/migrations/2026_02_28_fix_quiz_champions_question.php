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
        // Actualizar la pregunta incorrecta sobre "el único equipo español"
        // Barcelona también ha ganado Champions, por lo que la pregunta estaba mal
        DB::table('questions')
            ->where('title', 'LIKE', '%¿Cuál es el único equipo español%')
            ->where('title', 'LIKE', '%ganado la Champions%')
            ->update([
                'title' => '¿Cuál es el equipo español que más veces ha ganado la Champions League?'
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir al texto anterior
        DB::table('questions')
            ->where('title', 'LIKE', '%¿Cuál es el equipo español que más veces%')
            ->where('title', 'LIKE', '%ganado la Champions%')
            ->update([
                'title' => '¿Cuál es el único equipo español que ha ganado la Champions League?'
            ]);
    }
};
