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

it('rejects invalid pgn content', function (string $pgn) {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    $session = TrainingSession::factory()->create([
        'user_id' => $user->id,
    ]);

    Passport::actingAs($user);

    $this->postJson(
        apiUrl("training-sessions/{$session->id}/games"),
        ['pgn' => $pgn]
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('pgn');

    expect($session->games()->count())->toBe(0);
})->with([
    'invalid text' => ['Esto no es una partida'],

    'illegal pawn move' => ['1. e5 *'],

    'invalid notation' => ['1. Zk4 *'],

    'unclosed variation' => [
        '1. e4 e5 (1... c5 2. Nf3 *',
    ],
]);

it('accepts a pgn with a custom initial position', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    $session = TrainingSession::factory()->create([
        'user_id' => $user->id,
    ]);

    Passport::actingAs($user);

    $pgn = <<<'PGN'
    [SetUp "1"]
    [FEN "8/8/8/8/8/4k3/4P3/4K3 w - - 0 1"]

    *
    PGN;

    $this->postJson(
        apiUrl("training-sessions/{$session->id}/games"),
        ['pgn' => $pgn]
    )->assertCreated();
});

it('accepts valid pgn formats', function (string $pgn) {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    $session = TrainingSession::factory()->create([
        'user_id' => $user->id,
    ]);

    Passport::actingAs($user);

    $this->postJson(
        apiUrl("training-sessions/{$session->id}/games"),
        ['pgn' => $pgn]
    )
        ->assertCreated()
        ->assertJsonPath('data.pgn', $pgn);
})->with([
    'castling' => [
        '1. e4 e5 2. Nf3 Nc6 3. Bb5 a6 4. Ba4 Nf6 5. O-O *',
    ],

    'comments' => [
        '1. e4 {Controls the center} e5 2. Nf3 Nc6 *',
    ],

    'variations' => [
        '1. e4 e5 (1... c5 2. Nf3) 2. Nf3 Nc6 *',
    ],

    'nested variations' => [
        '1. e4 e5 (1... c5 (1... e6) 2. Nf3) 2. Nf3 Nc6 *',
    ],

    'black to move' => [
        '[SetUp "1"]
[FEN "8/4k3/8/8/4P3/8/4K3/8 b - - 0 1"]

1... Ke6 *',
    ],
]);