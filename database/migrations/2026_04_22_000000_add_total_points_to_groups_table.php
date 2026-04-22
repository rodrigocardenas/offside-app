<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            // Columna de caché para sumatoria de puntos
            $table->bigInteger('total_points')
                ->default(0)
                ->after('expires_at')
                ->comment('Sumatoria de todos los group_user.points');
            
            // Timestamp de última actualización
            $table->timestamp('total_points_updated_at')
                ->nullable()
                ->after('total_points')
                ->comment('Última actualización de total_points');
            
            // Índice para queries rápidas
            $table->index('total_points');
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropIndex(['total_points']);
            $table->dropColumn(['total_points', 'total_points_updated_at']);
        });
    }
};
