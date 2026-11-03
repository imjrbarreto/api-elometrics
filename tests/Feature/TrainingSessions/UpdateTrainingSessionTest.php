<?php

use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

it('can update a training session', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    $trainingSession = TrainingSession::factory()->create([
        'user_id' => $user->id,
        'title' => 'Finales de torres',
    ]);

    Passport::actingAs($user);

    $this->patchJson(apiUrl("training-sessions/{$trainingSession->id}"), [
        'title' => 'Finales de peones',
        'description' => 'Oposicion y ejercicios de finales de peones.',
        'category' => 'endgame',
        'resource' => 'https://lichess.org/study/efgh5678',
    ])->assertOk()
        ->assertJsonPath('data.title', 'Finales de peones')
        ->assertJsonPath(
            'data.description',
            'Oposicion y ejercicios de finales de peones.'
        )
        ->assertJsonPath('data.category', 'endgame')
        ->assertJsonPath('data.resource', 'https://lichess.org/study/efgh5678');

    $this->assertDatabaseHas('training_sessions', [
        'id' => $trainingSession->id,
        'title' => 'Finales de peones',
        'description' => 'Oposicion y ejercicios de finales de peones.',
        'category' => 'endgame',
        'resource' => 'https://lichess.org/study/efgh5678',
    ]);
});

it('rejects an empty title when updating a training session', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    $trainingSession = TrainingSession::factory()->create([
        'user_id' => $user->id,
        'title' => 'Finales de torres',
    ]);

    Passport::actingAs($user);

    $this->patchJson(apiUrl("training-sessions/{$trainingSession->id}"), [
        'title' => '',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);

    $this->assertDatabaseHas('training_sessions', [
        'id' => $trainingSession->id,
        'title' => 'Finales de torres',
    ]);
});