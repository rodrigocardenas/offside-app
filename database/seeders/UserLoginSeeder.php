<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Database\Seeder;

class UserLoginSeeder extends Seeder
{
    /**
     * Seed the application's database with recent login activity.
     */
    public function run(): void
    {
        if (User::count() === 0) {
            User::factory()->count(5)->create();
        }

        User::query()
            ->select('id')
            ->orderBy('id')
            ->each(function (User $user) {
                $entries = random_int(3, 7);

                UserLogin::factory()
                    ->count($entries)
                    ->for($user)
                    ->state(function () {
                        $minutesAgo = random_int(5, 10080); // up to 7 days
                        return [
                            'logged_in_at' => now()->subMinutes($minutesAgo),
                        ];
                    })
                    ->create();
            });
    }
}
