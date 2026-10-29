<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

it('can create training session', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Passport::actingAs($user);

    $this->postJson(apiUrl('training-sessions'), [
        'title' => 'Finales de torres',
        'description' => 'Actividad de la torre y ejercicios practicos.',
        'category' => 'endgame',
        'resource' => 'https://lichess.org/study/abcd1234',
    ])->assertCreated()
        ->assertJsonPath('data.title', 'Finales de torres')
        ->assertJsonPath('data.description', 'Actividad de la torre y ejercicios practicos.')
        ->assertJsonPath('data.category', 'endgame')
        ->assertJsonPath('data.resource', 'https://lichess.org/study/abcd1234');

    $this->assertDatabaseHas('training_sessions', [
        'title' => 'Finales de torres',
        'user_id' => $user->id,
        'description' => 'Actividad de la torre y ejercicios practicos.',
        'category' => 'endgame',
        'resource' => 'https://lichess.org/study/abcd1234',
    ]);
});


