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
        // Alterar el ENUM para incluir 'quiz'
        DB::statement("ALTER TABLE answers MODIFY category ENUM('predictive', 'social', 'quiz') DEFAULT 'predictive'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir al ENUM original (sin 'quiz')
        DB::statement("ALTER TABLE answers MODIFY category ENUM('predictive', 'social') DEFAULT 'predictive'");
    }
};
