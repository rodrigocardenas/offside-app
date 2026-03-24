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
        Schema::table('users', function (Blueprint $table) {
            // Add Cloudflare fields for avatar
            if (!Schema::hasColumn('users', 'avatar_cloudflare_id')) {
                $table->string('avatar_cloudflare_id')->nullable()->comment('Cloudflare Images ID for avatar');
            }
            if (!Schema::hasColumn('users', 'avatar_provider')) {
                $table->enum('avatar_provider', ['local', 'cloudflare'])->default('local')->comment('Image provider for avatar');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'avatar_cloudflare_id')) {
                $table->dropColumn('avatar_cloudflare_id');
            }
            if (Schema::hasColumn('users', 'avatar_provider')) {
                $table->dropColumn('avatar_provider');
            }
        });
    }
};
