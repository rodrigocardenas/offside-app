<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Eliminar duplicados antes de agregar el índice único:
        // Mantener solo el registro más reciente por device_token
        DB::statement('
            DELETE ps1 FROM push_subscriptions ps1
            INNER JOIN push_subscriptions ps2
            WHERE ps1.id < ps2.id AND ps1.device_token = ps2.device_token
        ');

        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->unique('device_token', 'push_subscriptions_device_token_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->dropUnique('push_subscriptions_device_token_unique');
        });
    }
};
