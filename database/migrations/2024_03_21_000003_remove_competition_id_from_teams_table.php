<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropForeign(['competition_id']);
            $table->dropColumn('competition_id');
        });
    }

    public function down()
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->foreignId('competition_id')->nullable()->constrained();
        });
    }
};
