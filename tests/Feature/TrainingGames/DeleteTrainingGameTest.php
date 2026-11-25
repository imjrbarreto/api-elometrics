<?php

use App\Models\User;
use App\Models\TrainingGame;
use App\Models\TrainingSession;
use Laravel\Passport\Passport;

it('allows the creator to delete a training game', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    $session = TrainingSession::factory()->create([
        'user_id' => $user->id,
    ]);

    $game = TrainingGame::factory()->create([
        'training_session_id' => $session->id,
    ]);

    Passport::actingAs($user);

    $this->deleteJson(
        apiUrl("training-sessions/{$session->id}/games/{$game->id}")
    )->assertNoContent();

    $this->assertDatabaseMissing('training_games', [
        'id' => $game->id,
    ]);
});

it('forbids deleting games from another user training session', function () {
    $owner = User::factory()->create();
    $user = User::factory()->create();

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $game = TrainingGame::factory()->create([
        'training_session_id' => $session->id,
    ]);

    Passport::actingAs($user);

    $this->deleteJson(
        apiUrl("training-sessions/{$session->id}/games/{$game->id}")
    )->assertForbidden();

    $this->assertDatabaseHas('training_games', [
        'id' => $game->id,
    ]);
});

it('returns not found when the game belongs to another session', function () {
    $user = User::factory()->create();

    $session = TrainingSession::factory()->create([
        'user_id' => $user->id,
    ]);

    $otherSession = TrainingSession::factory()->create([
        'user_id' => $user->id,
    ]);

    $game = TrainingGame::factory()->create([
        'training_session_id' => $otherSession->id,
    ]);

    Passport::actingAs($user);

    $this->deleteJson(
        apiUrl("training-sessions/{$session->id}/games/{$game->id}")
    )->assertNotFound();

    $this->assertDatabaseHas('training_games', [
        'id' => $game->id,
    ]);
});