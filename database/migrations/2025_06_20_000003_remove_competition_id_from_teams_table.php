<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('teams', function (Blueprint $table) {
            // Verificar si la foreign key existe antes de dropeaerla
            $this->dropForeignKeyIfExists('teams', 'teams_competition_id_foreign');
            
            if (Schema::hasColumn('teams', 'competition_id')) {
                $table->dropColumn('competition_id');
            }
        });
    }

    public function down()
    {
        Schema::table('teams', function (Blueprint $table) {
            if (!Schema::hasColumn('teams', 'competition_id')) {
                $table->foreignId('competition_id')->nullable()->constrained();
            }
        });
    }

    /**
     * Dropeaer foreign key si existe
     */
    private function dropForeignKeyIfExists($table, $foreignKey)
    {
        $constraint = \DB::select("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = ? 
            AND CONSTRAINT_NAME = ?
        ", [$table, $foreignKey]);
        
        if (!empty($constraint)) {
            \DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `$foreignKey`");
        }
    }
};
