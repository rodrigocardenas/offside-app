<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToTemplateQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('template_questions', function (Blueprint $table) {
            $table->unsignedBigInteger('competition_id')->nullable()->after('id'); // FK to competitions table
            $table->softDeletes()->after('dislikes'); // Adds deleted_at column for soft deletes
            $table->timestamp('used_at')->nullable()->after('deleted_at'); // Timestamp to track if the question has been used
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('template_questions', function (Blueprint $table) {
            $table->dropColumn(['competition_id', 'deleted_at', 'used_at']);
        });
    }
}
