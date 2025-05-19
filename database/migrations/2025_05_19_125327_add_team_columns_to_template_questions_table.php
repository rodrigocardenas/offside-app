<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('template_questions', function (Blueprint $table) {
            $table->foreignId('football_match_id')->nullable()->constrained('football_matches')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('template_questions', function (Blueprint $table) {
            $table->dropForeign(['football_match_id']);
            $table->dropColumn(['football_match_id']);
        });
    }
};
