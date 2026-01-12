<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración - Limpia tablas que pueden estar duplicadas
     */
    public function up(): void
    {
        $tablesToCheck = [
            'competition_team',
            'cache',
            'cache_locks',
            'push_subscriptions',
            'template_question_user_reaction',
            'chat_message_user',
            'gemini_analyses',
            'role_user',
            'group_user',
        ];
        
        foreach ($tablesToCheck as $table) {
            // Si la tabla existe y está duplicada/corrupta, intentar repararla
            if (Schema::hasTable($table)) {
                try {
                    // Usar REPAIR TABLE para verificar integridad
                    \DB::statement("REPAIR TABLE `$table`");
                } catch (\Exception $e) {
                    // Ignorar si falla la reparación
                    \Log::warning("No se pudo reparar tabla $table: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Revertir la migración
     */
    public function down(): void
    {
        // Esta migración de limpieza no se revierte
    }
};
