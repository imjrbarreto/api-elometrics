<?php

use App\Models\User;
use App\Models\TrainingGame;
use App\Models\TrainingSession;
use Laravel\Passport\Passport;

it('includes games in the personal training session details', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    $session = TrainingSession::factory()->create([
        'user_id' => $user->id,
    ]);

    TrainingGame::factory()->count(3)->create([
        'training_session_id' => $session->id,
    ]);

    Passport::actingAs($user);

    $this->getJson(
        apiUrl("training-sessions/{$session->id}")
    )
        ->assertOk()
        ->assertJsonCount(3, 'data.games');
});

it('includes games in linked training session details', function (string $role) {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $member = emMember($workspace, $role);

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $game = TrainingGame::factory()->create([
        'training_session_id' => $session->id,
    ]);

    $workspace->trainingSessions()->attach($session->id);

    Passport::actingAs($member);

    $this->getJson(
        apiUrl("workspaces/{$workspace->id}/training-sessions/{$session->id}")
    )
        ->assertOk()
        ->assertJsonCount(1, 'data.games')
        ->assertJsonPath('data.games.0.pgn', $game->pgn);
})->with(['coach', 'student', 'viewer']);

it('returns an empty games collection when the session has no games', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    $session = TrainingSession::factory()->create([
        'user_id' => $user->id,
    ]);

    Passport::actingAs($user);

    $this->getJson(
        apiUrl("training-sessions/{$session->id}")
    )
        ->assertOk()
        ->assertJsonCount(0, 'data.games');
});