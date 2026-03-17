<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('endpoint')->unique();
            $table->string('public_key')->nullable();
            $table->string('auth_token')->nullable();
            $table->string('device_token')->nullable();
            $table->string('platform')->default('web')->comment('Platform: web, android, ios');
            $table->timestamps();
            
            // Index for efficient searches
            $table->index(['user_id', 'platform']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
