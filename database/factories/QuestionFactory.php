<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'type' => $this->faker->randomElement(['predictive', 'social']),
            'group_id' => Group::factory(),
            'points' => $this->faker->numberBetween(1, 10),
            'available_until' => now()->addHours(2),
        ];
    }
}
