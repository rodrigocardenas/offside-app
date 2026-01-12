<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('competitions', function (Blueprint $table) {
            if (!Schema::hasColumn('competitions', 'crest_url')) {
                $table->string('crest_url')->nullable()->after('name');
            }
        });
    }

    public function down()
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn('crest_url');
        });
    }
};
