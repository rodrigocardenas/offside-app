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
        Schema::table('groups', function (Blueprint $table) {
            // Add Cloudflare fields for cover images
            if (!Schema::hasColumn('groups', 'cover_cloudflare_id')) {
                $table->string('cover_cloudflare_id')->nullable()->comment('Cloudflare Images ID for cover');
            }
            if (!Schema::hasColumn('groups', 'cover_provider')) {
                $table->enum('cover_provider', ['local', 'cloudflare'])->default('local')->comment('Image provider for cover');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            if (Schema::hasColumn('groups', 'cover_cloudflare_id')) {
                $table->dropColumn('cover_cloudflare_id');
            }
            if (Schema::hasColumn('groups', 'cover_provider')) {
                $table->dropColumn('cover_provider');
            }
        });
    }
};
