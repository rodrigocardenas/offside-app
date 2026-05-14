<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateImagesToR2 extends Command
{
    protected $signature = 'r2:migrate
                            {--type=all : Tipo de imágenes a migrar (avatars, covers, all)}
                            {--dry-run : Solo muestra qué se migraría sin hacer nada}';

    protected $description = 'Copia imágenes del storage local a Cloudflare R2 (nunca elimina el original)';

    public function handle(): int
    {
        if (!config('filesystems.disks.r2')) {
            $this->error('El disco r2 no está configurado en filesystems.php');
            return self::FAILURE;
        }

        $type = $this->option('type');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN — no se realizarán cambios');
        }

        if (in_array($type, ['avatars', 'all'])) {
            $this->migrateAvatars($dryRun);
        }

        if (in_array($type, ['covers', 'all'])) {
            $this->migrateGroupCovers($dryRun);
        }

        $this->info('✅ Migración completada');
        return self::SUCCESS;
    }

    private function migrateAvatars(bool $dryRun): void
    {
        $this->info('📁 Migrando avatars de usuarios...');

        $users = User::whereNotNull('avatar')
            ->where(function ($q) {
                $q->whereNull('avatar_provider')
                    ->orWhere('avatar_provider', 'local');
            })
            ->whereNull('r2_avatar')
            ->get(['id', 'name', 'avatar']);

        $this->info("   Encontrados: {$users->count()} usuarios con avatar local");

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $copied = 0;
        $errors = 0;

        foreach ($users as $user) {
            $localPath = 'avatars/' . $user->avatar;

            if (!Storage::disk('public')->exists($localPath)) {
                $bar->advance();
                continue;
            }

            $r2Path = 'avatars/' . $user->avatar;

            if ($dryRun) {
                $this->newLine();
                $this->line("   [dry-run] Copiaría: storage/public/{$localPath} → r2://{$r2Path}");
                $bar->advance();
                $copied++;
                continue;
            }

            try {
                $contents = Storage::disk('public')->get($localPath);
                $mimeType = Storage::disk('public')->mimeType($localPath);

                Storage::disk('r2')->put($r2Path, $contents, [
                    'visibility' => 'public',
                    'ContentType' => $mimeType,
                ]);

                $user->update(['r2_avatar' => $r2Path]);
                $copied++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("   Error con usuario {$user->id}: {$e->getMessage()}");
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("   ✓ Copiados: {$copied} | ✗ Errores: {$errors}");
    }

    private function migrateGroupCovers(bool $dryRun): void
    {
        $this->info('📁 Migrando portadas de grupos...');

        $groups = Group::whereNotNull('cover_image')
            ->where(function ($q) {
                $q->whereNull('cover_provider')
                    ->orWhere('cover_provider', 'local');
            })
            ->whereNull('r2_cover')
            ->get(['id', 'name', 'cover_image']);

        $this->info("   Encontrados: {$groups->count()} grupos con portada local");

        $bar = $this->output->createProgressBar($groups->count());
        $bar->start();

        $copied = 0;
        $errors = 0;

        foreach ($groups as $group) {
            $localPath = 'covers/' . $group->cover_image;

            if (!Storage::disk('public')->exists($localPath)) {
                $bar->advance();
                continue;
            }

            $r2Path = 'covers/' . $group->cover_image;

            if ($dryRun) {
                $this->newLine();
                $this->line("   [dry-run] Copiaría: storage/public/{$localPath} → r2://{$r2Path}");
                $bar->advance();
                $copied++;
                continue;
            }

            try {
                $contents = Storage::disk('public')->get($localPath);
                $mimeType = Storage::disk('public')->mimeType($localPath);

                Storage::disk('r2')->put($r2Path, $contents, [
                    'visibility' => 'public',
                    'ContentType' => $mimeType,
                ]);

                $group->update(['r2_cover' => $r2Path]);
                $copied++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("   Error con grupo {$group->id}: {$e->getMessage()}");
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("   ✓ Copiados: {$copied} | ✗ Errores: {$errors}");
    }
}
