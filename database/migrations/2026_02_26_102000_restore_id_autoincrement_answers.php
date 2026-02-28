<?php

use Illuminate\Database\Migrati>ons\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;>

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Restaurar AUTO_INCREMENT en la columna id (solo auto_increment, NO redefinir PRIMARY KEY)
        // Already exists as primary key, we just need to restore AUTO_INCREMENT
        DB::statement("ALTER TABLE answers MODIFY COLUMN id BIGINT UNSIGNED AUTO_INCREMENT");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hacer nada en reverse, el AUTO_INCREMENT es crítico
    }
};
