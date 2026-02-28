<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega columna `answered_at` a tabla answers para registrar
     * el momento exacto en que el usuario respondió.
     * Necesario para calcular tiempo total de respuesta en rankings de quiz.
     */
    public function up(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            // Agregar timestamp de cuando se respondió (para quiz ranking)
            if (!Schema::hasColumn('answers', 'answered_at')) {
                $table->timestamp('answered_at')
                    ->nullable()
                    ->after('points_earned')
                    ->comment('Momento exacto cuando se respondió (para quiz ranking)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            if (Schema::hasColumn('answers', 'answered_at')) {
                $table->dropColumn('answered_at');
            }
        });
    }
};
