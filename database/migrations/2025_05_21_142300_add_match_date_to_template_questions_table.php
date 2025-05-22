<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('template_questions', function (Blueprint $table) {
            $table->dateTime('match_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('template_questions', function (Blueprint $table) {
            $table->dropColumn('match_date');
        });
    }
};
