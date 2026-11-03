<?php

use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

it('can show a training session', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    $trainingSession = TrainingSession::factory()->create([
        'user_id' => $user->id,
        'title' => 'Finales de torres',
        'description' => 'Actividad de la torre y ejercicios practicos.',
        'category' => 'endgame',
        'resource' => 'https://lichess.org/study/abcd1234',
    ]);

    Passport::actingAs($user);

    $this->getJson(apiUrl("training-sessions/{$trainingSession->id}"))
        ->assertOk()
        ->assertJsonPath('data.id', $trainingSession->id)
        ->assertJsonPath('data.title', 'Finales de torres')
        ->assertJsonPath(
            'data.description',
            'Actividad de la torre y ejercicios practicos.'
        )
        ->assertJsonPath('data.category', 'endgame')
        ->assertJsonPath('data.resource', 'https://lichess.org/study/abcd1234');
});
