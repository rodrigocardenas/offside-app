<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Modificar la tabla football_matches para hacer el campo status nullable
        Schema::table('football_matches', function (Blueprint $table) {
            $table->string('status')->nullable()->change();
        });

        // Modificar la tabla answers para corregir la clave foránea
        Schema::table('answers', function (Blueprint $table) {
            if (Schema::hasColumn('answers', 'option_id')) {
                $table->dropForeign(['option_id']);
                $table->dropColumn('option_id');
            }
            if (!Schema::hasColumn('answers', 'question_option_id')) {
                $table->foreignId('question_option_id')->nullable()->constrained('question_options')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('football_matches', function (Blueprint $table) {
            $table->string('status')->nullable(false)->change();
        });

        Schema::table('answers', function (Blueprint $table) {
            if (Schema::hasColumn('answers', 'question_option_id')) {
                $table->dropForeign(['question_option_id']);
                $table->dropColumn('question_option_id');
            }
            if (!Schema::hasColumn('answers', 'option_id')) {
                $table->foreignId('option_id')->nullable()->constrained('question_options')->nullOnDelete();
            }
        });
    }
};
