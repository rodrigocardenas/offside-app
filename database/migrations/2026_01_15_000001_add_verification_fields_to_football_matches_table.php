<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('football_matches', function (Blueprint $table) {
            $table->timestamp('last_verification_attempt_at')->nullable();
            $table->tinyInteger('verification_priority')
                ->default(3)
                ->comment('1 = alta, 2 = media, 3 = baja');
        });
    }

    public function down(): void
    {
        Schema::table('football_matches', function (Blueprint $table) {
            $table->dropColumn(['last_verification_attempt_at', 'verification_priority']);
        });
    }
};
