<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('template_questions', function (Blueprint $table) {
            $table->unsignedInteger('likes')->default(0)->after('is_featured');
            $table->unsignedInteger('dislikes')->default(0)->after('likes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('template_questions', function (Blueprint $table) {
            $table->dropColumn(['likes', 'dislikes']);
        });
    }
};
