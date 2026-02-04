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
        Schema::table('push_subscriptions', function (Blueprint $table) {
            // Agregar columna platform para distinguir entre web, android, ios
            $table->string('platform')->default('web')->after('device_token');
            // Agregar índice compuesto para búsquedas eficientes
            $table->index(['user_id', 'platform']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'platform']);
            $table->dropColumn('platform');
        });
    }
};
