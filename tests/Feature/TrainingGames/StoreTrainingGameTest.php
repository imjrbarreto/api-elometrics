<?php

use App\Models\User;
use App\Models\TrainingSession;
use Laravel\Passport\Passport;

it('allows the creator to add a game to a training session', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    $session = TrainingSession::factory()->create([
        'user_id' => $user->id,
    ]);

    Passport::actingAs($user);

    $pgn = '1. e4 e5 2. Nf3 Nc6 *';

    $this->postJson(
        apiUrl("training-sessions/{$session->id}/games"),
        ['pgn' => $pgn]
    )
        ->assertCreated()
        ->assertJsonPath('data.pgn', $pgn);

    $this->assertDatabaseHas('training_games', [
        'training_session_id' => $session->id,
        'pgn' => $pgn,
    ]);
});

it('forbids adding games to another user training session', function () {
    /** @var \Tests\TestCase $this */

    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    Passport::actingAs($otherUser);

    $this->postJson(
        apiUrl("training-sessions/{$session->id}/games"),
        ['pgn' => '1. e4 e5 *']
    )->assertForbidden();
});

it('requires a pgn when creating a training game', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    $session = TrainingSession::factory()->create([
        'user_id' => $user->id,
    ]);

    Passport::actingAs($user);

    $this->postJson(
        apiUrl("training-sessions/{$session->id}/games"),
        []
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('pgn');
});

it('allows adding multiple games to a training session', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    $session = TrainingSession::factory()->create([
        'user_id' => $user->id,
    ]);

    Passport::actingAs($user);

    foreach (['1. e4 e5 *', '1. d4 d5 *'] as $pgn) {
        $this->postJson(
            apiUrl("training-sessions/{$session->id}/games"),
            ['pgn' => $pgn]
        )->assertCreated();
    }

    expect($session->games()->count())->toBe(2);
});

it('requires authentication to add a training game', function () {
    /** @var \Tests\TestCase $this */
    
    $session = TrainingSession::factory()->create();

    $this->postJson(
        apiUrl("training-sessions/{$session->id}/games"),
        ['pgn' => '1. e4 e5 *']
    )->assertUnauthorized();
});