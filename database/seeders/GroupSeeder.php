<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Str;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        // Crear algunos usuarios de prueba si no existen
        $users = User::factory(5)->create();

        // Crear algunos grupos
        foreach ($users as $user) {
            $group = Group::create([
                'name' => 'Grupo de ' . $user->name,
                'code' => Str::random(6),
                'created_by' => $user->id
            ]);

            // Agregar algunos usuarios aleatorios al grupo
            $randomUsers = User::inRandomOrder()->limit(3)->get();
            $group->users()->attach($randomUsers->pluck('id'));
        }
    }
}
