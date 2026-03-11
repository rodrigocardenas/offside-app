<?php

namespace App\Console\Commands;

use App\Models\Group;
use Illuminate\Console\Command;

class DeleteExpiredGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'groups:delete-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired public groups that are past their expiration date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deletedCount = Group::where('category', 'public')
            ->where('expires_at', '<', now())
            ->delete();

        $this->info("✓ Eliminados {$deletedCount} grupos públicos expirados");

        return Command::SUCCESS;
    }
}
