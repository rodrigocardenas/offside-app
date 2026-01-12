<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración - Limpia y corrige todas las columnas problemáticas en teams
     */
    public function up(): void
    {
        if (Schema::hasTable('teams')) {
            Schema::table('teams', function (Blueprint $table) {
                // Agregar columnas que puedan faltar
                if (!Schema::hasColumn('teams', 'type')) {
                    $table->string('type')->default('club')->after('name');
                }
                
                // Asegurar que external_id existe
                if (!Schema::hasColumn('teams', 'external_id')) {
                    $table->string('external_id')->nullable()->after('id');
                }
                
                // Asegurar que country existe
                if (!Schema::hasColumn('teams', 'country')) {
                    $table->string('country')->nullable()->after('short_name');
                }
                
                // Asegurar que competition_id existe
                if (!Schema::hasColumn('teams', 'competition_id')) {
                    $table->unsignedBigInteger('competition_id')->nullable();
                }
            });
            
            // Agregar foreign keys de manera segura
            $this->addForeignKeyIfNotExists('teams', 'competition_id', 'competitions');
        }
    }

    /**
     * Revertir la migración
     */
    public function down(): void
    {
        // Esta migración de corrección no se revierte
    }
    
    /**
     * Agregar una foreign key de manera segura
     */
    private function addForeignKeyIfNotExists(string $table, string $column, string $referenceTable): void
    {
        // Consultar si la foreign key ya existe
        $constraint = \DB::select("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = ? 
            AND COLUMN_NAME = ? 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$table, $column]);
        
        // Si no existe, crearla
        if (empty($constraint)) {
            try {
                Schema::table($table, function (Blueprint $table) use ($column, $referenceTable) {
                    $table->foreign($column)->references('id')->on($referenceTable)->nullOnDelete();
                });
            } catch (\Exception $e) {
                // Silenciar errores si la foreign key ya existe o no se puede crear
                \Log::warning("No se pudo crear foreign key $table.$column: " . $e->getMessage());
            }
        }
    }
};
