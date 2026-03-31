<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActionTemplate>
 */
class ActionTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['GOALS', 'CARDS', 'DEFENSE', 'RARE', 'FUNNY'];
        
        return [
            'action' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(5),
            'probability' => $this->faker->randomFloat(2, 0.01, 0.75),
            'category' => $this->faker->randomElement($categories),
        ];
    }
}
