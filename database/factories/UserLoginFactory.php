<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserLogin>
 */
class UserLoginFactory extends Factory
{
    protected $model = UserLogin::class;

    public function definition(): array
    {
        $devices = [
            'iPhone 15 Pro',
            'Pixel 8',
            'iPad Air',
            'Galaxy S24',
            'MacBook Pro',
            'Windows Desktop',
        ];

        return [
            'user_id' => User::factory(),
            'ip_address' => $this->faker->ipv4(),
            'device' => $this->faker->randomElement($devices),
            'user_agent' => $this->faker->userAgent(),
            'logged_in_at' => $this->faker->dateTimeBetween('-10 days', 'now'),
        ];
    }
}
