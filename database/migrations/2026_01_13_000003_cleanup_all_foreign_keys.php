<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ejecutar la migración - Limpiar foreign keys problemáticas
     */
    public function up(): void
    {
        // Opción: Desabilitar constraint checking temporalmente
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        try {
            // Limpiar todas las foreign keys problemáticas de manera segura
            $this->cleanupForeignKeys();
        } finally {
            // Reabilitar constraint checking
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Revertir la migración
     */
    public function down(): void
    {
        // Esta migración de limpieza no se revierte
    }
    
    /**
     * Limpiar todas las foreign keys que puedan estar en mal estado
     */
    private function cleanupForeignKeys(): void
    {
        $foreignKeysToCheck = [
            'teams' => ['teams_competition_id_foreign', 'teams_competitions_foreign'],
            'questions' => [
                'questions_football_match_id_foreign',
                'questions_match_id_foreign',
                'questions_user_id_foreign',
                'questions_template_question_id_foreign',
                'questions_competition_id_foreign'
            ],
            'answers' => [
                'answers_option_id_foreign',
                'answers_question_option_id_foreign',
            ],
            'football_matches' => [
                'football_matches_home_team_id_foreign',
                'football_matches_away_team_id_foreign',
                'football_matches_stadium_id_foreign',
                'football_matches_competition_id_foreign',
            ],
            'template_questions' => [
                'template_questions_football_match_id_foreign',
                'template_questions_home_team_id_foreign',
                'template_questions_away_team_id_foreign',
            ],
            'users' => [
                'users_favorite_competition_id_foreign',
                'users_favorite_club_id_foreign',
                'users_favorite_national_team_id_foreign',
            ]
        ];
        
        foreach ($foreignKeysToCheck as $table => $foreignKeys) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            
            foreach ($foreignKeys as $fkName) {
                $this->dropForeignKeyIfExists($table, $fkName);
            }
        }
    }
    
    /**
     * Dropeaer foreign key si existe
     */
    private function dropForeignKeyIfExists(string $table, string $foreignKey): void
    {
        $constraint = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = ? 
            AND CONSTRAINT_NAME = ?
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$table, $foreignKey]);
        
        if (!empty($constraint)) {
            try {
                DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `$foreignKey`");
                \Log::info("Foreign key eliminada: $table.$foreignKey");
            } catch (\Exception $e) {
                \Log::warning("No se pudo eliminar foreign key $table.$foreignKey: " . $e->getMessage());
            }
        }
    }
};
