<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EnsureStorageSymlink extends Command
{
    protected $signature = 'storage:ensure-symlink';
    protected $description = 'Ensure storage symlink exists (production safe)';

    public function handle()
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        $this->info("Storage symlink verification:");
        $this->info("Link path: {$link}");
        $this->info("Target path: {$target}");

        // Si ya existe un symlink válido
        if (is_link($link) && is_dir($link)) {
            $this->info("✅ Symlink já existe e aponta corretamente");
            $this->line("Target: " . readlink($link));
            return 0;
        }

        // Si existe un symlink roto
        if (is_link($link) && !is_dir($link)) {
            $this->warn("⚠️  Symlink roto detectado");
            unlink($link);
            $this->info("Symlink roto removido");
        }

        // Se existe um directório comum, mover
        if (is_dir($link) && !is_link($link)) {
            $this->warn("⚠️  Directório comum detectado, moviendo...");
            rename($link, $link . '.bak');
            $this->info("Directório movido a {$link}.bak");
        }

        // Criar o symlink
        try {
            if (!file_exists($target)) {
                $this->error("❌ Target path não existe: {$target}");
                return 1;
            }

            // Crear symlink
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows: usar mklink (requer admin)
                $this->warn("Windows detectado. Use: mklink /D \"" . str_replace('/', '\\', $link) . "\" \"" . str_replace('/', '\\', $target) . "\"");
                return 1;
            } else {
                // Unix/Linux
                $relativeTarget = '../storage/app/public';
                symlink($relativeTarget, $link);
                $this->info("✅ Symlink criado com sucesso");
                $this->line("Target: " . readlink($link));
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Erro ao criar symlink: " . $e->getMessage());
            return 1;
        }
    }
}
