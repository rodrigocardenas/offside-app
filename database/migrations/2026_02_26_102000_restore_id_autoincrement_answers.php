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
        // Restaurar AUTO_INCREMENT en la columna id
        DB::statement("ALTER TABLE answers MODIFY COLUMN id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover AUTO_INCREMENT (revert to non-auto-incrementing)
        DB::statement("ALTER TABLE answers MODIFY COLUMN id BIGINT UNSIGNED");
    }
};
