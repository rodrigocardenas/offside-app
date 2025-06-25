<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class CleanupOrphanedAvatars extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'avatars:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up orphaned avatar files that are not referenced by any user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando limpieza de avatares huérfanos...');

        // Obtener todos los archivos de avatar en el storage
        $avatarFiles = Storage::disk('public')->files('avatars');

        // Obtener todos los nombres de archivo de avatar de los usuarios
        $userAvatars = User::whereNotNull('avatar')->pluck('avatar')->toArray();

        $orphanedCount = 0;
        $deletedCount = 0;

        foreach ($avatarFiles as $file) {
            $filename = basename($file);

            if (!in_array($filename, $userAvatars)) {
                $this->line("Archivo huérfano encontrado: {$filename}");
                $orphanedCount++;

                // Eliminar automáticamente sin preguntar
                Storage::disk('public')->delete($file);
                $deletedCount++;
                $this->info("Archivo {$filename} eliminado.");
            }
        }

        // Limpiar usuarios con avatares que no existen
        $usersWithInvalidAvatars = User::whereNotNull('avatar')->get();

        foreach ($usersWithInvalidAvatars as $user) {
            if (!Storage::disk('public')->exists('avatars/' . $user->avatar)) {
                $this->line("Usuario {$user->name} tiene avatar inválido: {$user->avatar}");
                $user->update(['avatar' => null]);
                $this->info("Avatar de {$user->name} limpiado.");
            }
        }

        $this->info("Limpieza completada. Archivos huérfanos encontrados: {$orphanedCount}, eliminados: {$deletedCount}");

        return 0;
    }
}
