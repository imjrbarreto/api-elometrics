<?php

use App\Models\TrainingGame;
use App\Models\TrainingSession;

it('allows a training session to contain multiple games', function () {
    $session = TrainingSession::factory()->create();

    TrainingGame::factory()->count(3)->create([
        'training_session_id' => $session->id,
    ]);

    expect($session->games)->toHaveCount(3);
});

it('belongs to a training session', function () {
    $session = TrainingSession::factory()->create();

    $game = TrainingGame::factory()->create([
        'training_session_id' => $session->id,
    ]);

    expect($game->trainingSession->is($session))->toBeTrue();
});

it('deletes games when their training session is deleted', function () {
    $session = TrainingSession::factory()->create();

    TrainingGame::factory()->count(3)->create([
        'training_session_id' => $session->id,
    ]);

    $session->delete();

    expect(
        TrainingGame::where('training_session_id', $session->id)->count()
    )->toBe(0);
});