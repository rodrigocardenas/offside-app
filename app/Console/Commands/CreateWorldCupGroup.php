<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\Group;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateWorldCupGroup extends Command
{
    protected $signature = 'worldcup:create-group
                            {--admin-id= : User ID to set as group creator/admin (defaults to first admin user)}
                            {--force : Re-create group even if one already exists}';

    protected $description = 'Crea el grupo público del Mundial 2026 si no existe aún';

    public function handle(): int
    {
        $force = $this->option('force');

        // Check if WC group already exists
        $existing = Group::worldCup()->first();

        if ($existing && !$force) {
            $this->info("ℹ  El grupo del Mundial ya existe: \"{$existing->name}\" (id: {$existing->id})");
            return Command::SUCCESS;
        }

        if ($existing && $force) {
            $this->warn("--force: eliminando grupo existente id {$existing->id}...");
            $existing->delete();
        }

        // Resolve admin user
        $adminId = $this->option('admin-id');
        if ($adminId) {
            $admin = User::find($adminId);
        } else {
            $admin = User::orderBy('id')->first();
        }

        if (!$admin) {
            $this->error("❌ No se encontró un usuario para asignar como creador del grupo.");
            return Command::FAILURE;
        }

        // Resolve competition
        $competition = Competition::where('type', 'WC')->first();
        if (!$competition) {
            $this->warn("Competition WC no encontrada en BD. Ejecuta worldcup:import-matches primero.");
            $this->line("Continuando sin competition_id...");
        }

        // Create the group
        $group = Group::create([
            'name'           => '⚽ Mundial 2026',
            'code'           => 'MUNDIAL2026-' . strtoupper(Str::random(4)),
            'created_by'     => $admin->id,
            'category'       => 'public',
            'is_world_cup'   => true,
            'competition_id' => $competition?->id,
        ]);

        // Add admin as a member
        $group->users()->syncWithoutDetaching([
            $admin->id => ['is_admin' => true],
        ]);

        $this->info("✅ Grupo del Mundial creado:");
        $this->line("   ID   : {$group->id}");
        $this->line("   Nombre: {$group->name}");
        $this->line("   Código: {$group->code}");
        $this->line("   Admin : {$admin->name} (id: {$admin->id})");

        Log::info("Grupo del Mundial 2026 creado", [
            'group_id'   => $group->id,
            'group_name' => $group->name,
            'created_by' => $admin->id,
        ]);

        return Command::SUCCESS;
    }
}
