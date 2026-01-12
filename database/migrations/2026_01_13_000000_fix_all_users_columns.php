<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ejecutar la migración - Limpia y corrige todas las columnas problemáticas
     */
    public function up(): void
    {
        // Opción 1: Si la tabla users existe, verificar y agregar solo las columnas que falten
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Avatar
                if (!Schema::hasColumn('users', 'avatar')) {
                    $table->string('avatar')->nullable()->after('email');
                }
                
                // Is Admin
                if (!Schema::hasColumn('users', 'is_admin')) {
                    $table->boolean('is_admin')->default(false)->after('password');
                }
                
                // Theme
                if (!Schema::hasColumn('users', 'theme')) {
                    $table->string('theme')->default('dark')->after('remember_token');
                }
                
                // Theme Mode
                if (!Schema::hasColumn('users', 'theme_mode')) {
                    $table->string('theme_mode')->default('auto')->nullable()->after('theme');
                }
                
                // Language
                if (!Schema::hasColumn('users', 'language')) {
                    $table->string('language')->default('es')->nullable()->after('theme_mode');
                }
                
                // Favorite Competition ID
                if (!Schema::hasColumn('users', 'favorite_competition_id')) {
                    $table->unsignedBigInteger('favorite_competition_id')->nullable();
                }
                
                // Favorite Club ID
                if (!Schema::hasColumn('users', 'favorite_club_id')) {
                    $table->unsignedBigInteger('favorite_club_id')->nullable();
                }
                
                // Favorite National Team ID
                if (!Schema::hasColumn('users', 'favorite_national_team_id')) {
                    $table->unsignedBigInteger('favorite_national_team_id')->nullable();
                }
            });
            
            // Agregar foreign keys si no existen
            $this->addForeignKeys();
            
            // Limpiar datos inválidos
            DB::statement("UPDATE users SET language = 'es' WHERE language IS NULL OR language = '' OR language = 'NULL'");
        }
    }

    /**
     * Revertir la migración
     */
    public function down(): void
    {
        // Esta migración no se puede revertir completamente, solo deshabilitamos foreign keys
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Intentar eliminar foreign keys
                $this->dropForeignKeysIfExist($table);
            });
        }
    }
    
    /**
     * Agregar foreign keys de manera segura
     */
    private function addForeignKeys(): void
    {
        // Verificar si ya existe la foreign key antes de agregarla
        $this->addForeignKeyIfNotExists('users', 'favorite_competition_id', 'competitions');
        $this->addForeignKeyIfNotExists('users', 'favorite_club_id', 'teams');
        $this->addForeignKeyIfNotExists('users', 'favorite_national_team_id', 'teams');
    }
    
    /**
     * Agregar una foreign key de manera segura
     */
    private function addForeignKeyIfNotExists(string $table, string $column, string $referenceTable): void
    {
        $keyName = $table . '_' . $column . '_foreign';
        
        // Consultar si la foreign key ya existe
        $constraint = DB::select("
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
                    if ($column === 'favorite_competition_id') {
                        $table->foreign($column)->references('id')->on($referenceTable)->nullOnDelete();
                    } else {
                        $table->foreign($column)->references('id')->on($referenceTable)->nullOnDelete();
                    }
                });
            } catch (\Exception $e) {
                // Silenciar errores si la foreign key ya existe o no se puede crear
                \Log::warning("No se pudo crear foreign key $keyName: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Eliminar foreign keys si existen
     */
    private function dropForeignKeysIfExist($table): void
    {
        try {
            $table->dropForeign(['favorite_competition_id']);
        } catch (\Exception $e) {
            // Ignorar si no existe
        }
        
        try {
            $table->dropForeign(['favorite_club_id']);
        } catch (\Exception $e) {
            // Ignorar si no existe
        }
        
        try {
            $table->dropForeign(['favorite_national_team_id']);
        } catch (\Exception $e) {
            // Ignorar si no existe
        }
    }
};
