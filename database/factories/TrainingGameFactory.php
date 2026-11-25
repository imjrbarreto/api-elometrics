<?php

namespace Database\Factories;

use App\Models\TrainingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainingGameFactory extends Factory
{
    public function definition(): array
    {
        return [
            'training_session_id' => TrainingSession::factory(),
            'pgn' => '1. e4 e5 2. Nf3 Nc6 3. Bb5 a6 *',
        ];
    }
}