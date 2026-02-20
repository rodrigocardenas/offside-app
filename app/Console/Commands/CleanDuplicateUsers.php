<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class CleanDuplicateUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:clean-duplicates {--delete : Elimina los duplicados. Sin esta flag, solo muestra lo que se eliminaría}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detecta y elimina usuarios duplicados creados por ataque de spam';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Buscando usuarios duplicados...');

        // Obtener usuarios agrupados por nombre
        $duplicates = User::selectRaw('name, COUNT(*) as count')
            ->groupBy('name')
            ->having('count', '>', 1)
            ->with('roles') // Para obtener rolesyoutube
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('✅ No se encontraron usuarios duplicados.');
            return;
        }

        $this->warn("⚠️  Se encontraron " . $duplicates->count() . " usuarios duplicados:\n");

        $totalToDelete = 0;

        foreach ($duplicates as $duplicate) {
            $users = User::where('name', $duplicate->name)
                ->orderBy('created_at', 'asc') // Mantener el más antiguo
                ->get();

            $this->line("📌 Username: <comment>{$duplicate->name}</comment> ({$users->count()} usuarios)");

            // Mantener el primero, marcar el resto para eliminar
            $keepUser = $users->first();
            $toDelete = $users->skip(1);

            foreach ($toDelete as $user) {
                $totalToDelete++;
                $this->line("   ❌ ID {$user->id} | Email: {$user->email} | Creado: {$user->created_at}");
            }

            $this->line("   ✅ ID {$keepUser->id} | Email: {$keepUser->email} | Creado: {$keepUser->created_at} (MANTENER)");
            $this->newLine();
        }

        if ($totalToDelete === 0) {
            $this->info('No hay duplicados para eliminar.');
            return;
        }

        $this->warn("\nTotal a eliminar: <comment>$totalToDelete</comment> usuarios");

        if (!$this->option('delete')) {
            $this->info("\n💡 Ejecuta de nuevo con <comment>--delete</comment> para eliminar estos usuarios.");
            $this->info("   Ejemplo: php artisan users:clean-duplicates --delete");
            return;
        }

        if (!$this->confirm("⚠️  ¿Deseas eliminar $totalToDelete usuarios? Esto NO es reversible.")) {
            $this->info('Operación cancelada.');
            return;
        }

        // Eliminar duplicados
        foreach ($duplicates as $duplicate) {
            $users = User::where('name', $duplicate->name)
                ->orderBy('created_at', 'asc')
                ->get();

            $keepUser = $users->first();
            $deleted = 0;

            foreach ($users->skip(1) as $user) {
                // Log antes de eliminar
                Log::warning('Eliminando usuario duplicado', [
                    'user_id' => $user->id,
                    'username' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                    'keep_user_id' => $keepUser->id,
                ]);

                $user->delete();
                $deleted++;
            }

            $this->line("🗑️  Eliminados <info>$deleted</info> duplicados de '{$duplicate->name}'");
        }

        $this->info("\n✅ Limpieza completada. Se eliminaron <info>$totalToDelete</info> usuarios.");
        Log::info('Duplicate users cleanup completed', ['deleted_count' => $totalToDelete]);
    }
}
