<?php

namespace Database\Factories;

use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingSession>
 */
class TrainingSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), 
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(8),
            'category' => $this->faker->randomElement(['endgame', 'tactic', 'strategy', 'opening']),
            'resource' => $this->faker->url()
        ];
    }
}
