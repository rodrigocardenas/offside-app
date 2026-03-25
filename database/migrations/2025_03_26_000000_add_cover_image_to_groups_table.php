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
            // Add cover_image column if it doesn't exist
            if (!Schema::hasColumn('groups', 'cover_image')) {
                $table->string('cover_image')->nullable()->after('cover_provider');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            if (Schema::hasColumn('groups', 'cover_image')) {
                $table->dropColumn('cover_image');
            }
        });
    }
};
