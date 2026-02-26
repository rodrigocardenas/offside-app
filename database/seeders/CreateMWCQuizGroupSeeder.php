<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CreateMWCQuizGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Crea un grupo público dedicado para el Mobile World Congress Quiz.
     * Este grupo contendrá 10 preguntas de tipo 'quiz'.
     */
    public function run(): void
    {
        // Buscar o crear usuario admin para el grupo
        $admin = User::where('email', 'admin@offsideclub.com')->first();
        
        if (!$admin) {
            $admin = User::create([
                'name' => 'Admin',
                'email' => 'admin@offsideclub.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
        }

        // Crear grupo público MWC Quiz
        $mwcGroup = Group::firstOrCreate(
            ['code' => 'MWC-2026-QUIZ'],
            [
                'name' => 'Mobile World Congress Quiz 2026',
                'code' => 'MWC-2026-QUIZ',
                'created_by' => $admin->id,
                'category' => 'quiz',
                'reward_or_penalty' => null,
            ]
        );

        $this->command->info("✅ Grupo MWC Quiz creado: {$mwcGroup->name} (ID: {$mwcGroup->id})");
    }
}
