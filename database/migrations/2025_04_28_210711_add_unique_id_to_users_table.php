<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Eliminar la columna si existe
        if (Schema::hasColumn('users', 'unique_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('unique_id');
            });
        }

        // Agregar la columna
        Schema::table('users', function (Blueprint $table) {
            $table->string('unique_id', 50)->nullable()->after('id');
        });

        // Generar IDs únicos para usuarios existentes
        $users = DB::table('users')->whereNull('unique_id')->get();
        foreach ($users as $user) {
            do {
                $uniqueId = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
                $fullUniqueId = $user->name . '#' . $uniqueId;
            } while (DB::table('users')->where('unique_id', $fullUniqueId)->exists());

            DB::table('users')
                ->where('id', $user->id)
                ->update(['unique_id' => $fullUniqueId]);
        }

        // Hacer el campo único después de asignar IDs
        Schema::table('users', function (Blueprint $table) {
            $table->string('unique_id', 50)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'unique_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('unique_id');
            });
        }
    }
};
