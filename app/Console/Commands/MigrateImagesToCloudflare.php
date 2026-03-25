<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Group;
use App\Facades\CloudflareImages;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MigrateImagesToCloudflare extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloudflare:migrate 
                            {--type=avatars : Type of images to migrate (avatars, covers, or all)}
                            {--limit=0 : Limit number of images to migrate (0 = unlimited)}
                            {--force : Skip confirmation prompt}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Migrate existing images from local storage to Cloudflare Images';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!config('cloudflare.enabled')) {
            $this->error('✗ Cloudflare Images is not enabled. Enable it in config/cloudflare.php');
            return self::FAILURE;
        }

        $type = $this->option('type');
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        // Validate type option
        if (!in_array($type, ['avatars', 'covers', 'all'])) {
            $this->error('Invalid type: ' . $type . '. Use avatars, covers, or all');
            return self::FAILURE;
        }

        // Confirmation prompt
        if (!$force) {
            $this->warn('This command will migrate images to Cloudflare.');
            if (!$this->confirm('Do you want to continue?')) {
                $this->info('Migration cancelled.');
                return self::FAILURE;
            }
        }

        $totalMigrated = 0;
        $totalSkipped = 0;
        $totalFailed = 0;

        // Migrate avatars
        if ($type === 'avatars' || $type === 'all') {
            $result = $this->migrateAvatars($limit);
            $totalMigrated += $result['migrated'];
            $totalSkipped += $result['skipped'];
            $totalFailed += $result['failed'];
        }

        // Migrate group covers
        if ($type === 'covers' || $type === 'all') {
            $result = $this->migrateCovers($limit);
            $totalMigrated += $result['migrated'];
            $totalSkipped += $result['skipped'];
            $totalFailed += $result['failed'];
        }

        // Summary
        $this->newLine();
        $this->info('═════════════════════════════════════════');
        $this->info('Migration Summary');
        $this->info('═════════════════════════════════════════');
        $this->line('✓ Migrated: <fg=green>' . $totalMigrated . '</>');
        $this->line('⊘ Skipped: <fg=yellow>' . $totalSkipped . '</>');
        $this->line('✗ Failed: <fg=red>' . $totalFailed . '</>');
        $this->info('═════════════════════════════════════════');

        return self::SUCCESS;
    }

    /**
     * Migrate user avatars from local to Cloudflare
     */
    private function migrateAvatars(?int $limit = 0): array
    {
        $this->newLine();
        $this->info('🖼️  Migrating User Avatars...');

        $query = User::where('avatar_provider', 'local')
            ->whereNotNull('avatar')
            ->orderBy('created_at', 'desc');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $users = $query->get();
        $total = $users->count();

        if ($total === 0) {
            $this->info('No local avatars to migrate.');
            return ['migrated' => 0, 'skipped' => 0, 'failed' => 0];
        }

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $migrated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($users as $user) {
            try {
                $avatarPath = 'avatars/' . $user->avatar;
                
                // Check if file exists in local storage
                if (!Storage::disk('public')->exists($avatarPath)) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                // Get file content
                $fileContent = Storage::disk('public')->get($avatarPath);
                
                // Create temporary file
                $tempFile = tempnam(sys_get_temp_dir(), 'avatar_');
                file_put_contents($tempFile, $fileContent);
                
                // Upload to Cloudflare
                $uploadResponse = CloudflareImages::upload(
                    fopen($tempFile, 'r'),
                    'avatar_' . $user->id . '_migration',
                    ['user_id' => $user->id, 'migrated_at' => now()->toIso8601String()]
                );

                if ($uploadResponse && isset($uploadResponse['result']['id'])) {
                    // Update user record
                    $user->update([
                        'avatar_cloudflare_id' => $uploadResponse['result']['id'],
                        'avatar_provider' => 'cloudflare',
                    ]);

                    // Delete local file (optional - commented out for safety)
                    // Storage::disk('public')->delete($avatarPath);

                    $migrated++;
                    $this->info(" ✓ Migrated avatar for {$user->name}");
                    Log::info("Migrated avatar for user {$user->id}", ['cloudflare_id' => $uploadResponse['result']['id']]);
                } else {
                    $failed++;
                    $this->error(" ✗ Failed to upload {$user->name}'s avatar to Cloudflare");
                    Log::error("Failed to upload avatar to Cloudflare for user {$user->id}");
                }

                // Clean up temp file
                @unlink($tempFile);

            } catch (\Exception $e) {
                $failed++;
                $this->error(" ✗ Error migrating avatar for {$user->name}: " . $e->getMessage());
                Log::error("Error migrating avatar for user {$user->id}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return ['migrated' => $migrated, 'skipped' => $skipped, 'failed' => $failed];
    }

    /**
     * Migrate group cover images from local to Cloudflare
     */
    private function migrateCovers(?int $limit = 0): array
    {
        $this->newLine();
        $this->info('🎨 Migrating Group Covers...');

        $query = Group::where('cover_provider', 'local')
            ->whereNotNull('cover_image')
            ->orderBy('created_at', 'desc');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $groups = $query->get();
        $total = $groups->count();

        if ($total === 0) {
            $this->info('No local covers to migrate.');
            return ['migrated' => 0, 'skipped' => 0, 'failed' => 0];
        }

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $migrated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($groups as $group) {
            try {
                $coverPath = 'covers/' . $group->cover_image;
                
                // Check if file exists in local storage
                if (!Storage::disk('public')->exists($coverPath)) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                // Get file content
                $fileContent = Storage::disk('public')->get($coverPath);
                
                // Create temporary file
                $tempFile = tempnam(sys_get_temp_dir(), 'cover_');
                file_put_contents($tempFile, $fileContent);
                
                // Upload to Cloudflare
                $uploadResponse = CloudflareImages::upload(
                    fopen($tempFile, 'r'),
                    'group_cover_' . $group->id . '_migration',
                    ['group_id' => $group->id, 'type' => 'cover', 'migrated_at' => now()->toIso8601String()]
                );

                if ($uploadResponse && isset($uploadResponse['result']['id'])) {
                    // Update group record
                    $group->update([
                        'cover_cloudflare_id' => $uploadResponse['result']['id'],
                        'cover_provider' => 'cloudflare',
                    ]);

                    // Delete local file (optional - commented out for safety)
                    // Storage::disk('public')->delete($coverPath);

                    $migrated++;
                    $this->info(" ✓ Migrated cover for {$group->name}");
                    Log::info("Migrated cover for group {$group->id}", ['cloudflare_id' => $uploadResponse['result']['id']]);
                } else {
                    $failed++;
                    $this->error(" ✗ Failed to upload {$group->name}'s cover to Cloudflare");
                    Log::error("Failed to upload cover to Cloudflare for group {$group->id}");
                }

                // Clean up temp file
                @unlink($tempFile);

            } catch (\Exception $e) {
                $failed++;
                $this->error(" ✗ Error migrating cover for {$group->name}: " . $e->getMessage());
                Log::error("Error migrating cover for group {$group->id}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return ['migrated' => $migrated, 'skipped' => $skipped, 'failed' => $failed];
    }
}
