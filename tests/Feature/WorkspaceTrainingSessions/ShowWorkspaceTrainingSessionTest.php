<?php

use App\Models\TrainingSession;
use Laravel\Passport\Passport;

it('allows the owner to view a linked training session', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
        'title' => 'Rook endgames',
    ]);

    $workspace->trainingSessions()->attach($session->id);

    Passport::actingAs($owner);

    $this->getJson(apiUrl("workspaces/{$workspace->id}/training-sessions/{$session->id}"))
        ->assertOk()
        ->assertJsonPath('data.id', $session->id)
        ->assertJsonPath('data.title', 'Rook endgames');
});

it('allows workspace members to view a linked training session', function (string $role) {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $member = emMember($workspace, $role);

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($session->id);

    Passport::actingAs($member);

    $this->getJson(apiUrl("workspaces/{$workspace->id}/training-sessions/{$session->id}"))
        ->assertOk()
        ->assertJsonPath('data.id', $session->id);
})->with(['coach', 'student', 'viewer']);

it('forbids users outside the workspace from viewing a linked session', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($session->id);

    [, $otherWorkspace] = emWorkspace();

    $coach = emMember($otherWorkspace, 'coach');

    Passport::actingAs($coach);

    $this->getJson(apiUrl("workspaces/{$workspace->id}/training-sessions/{$session->id}"))
      ->assertForbidden();
});

it('returns not found when the session is not linked to the workspace', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    Passport::actingAs($owner);

    $this->getJson(apiUrl("workspaces/{$workspace->id}/training-sessions/{$session->id}"))
      ->assertNotFound();
});

it('requires authentication to view a linked training session', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($session->id);

    $this->getJson(apiUrl("workspaces/{$workspace->id}/training-sessions/{$session->id}"))
      ->assertUnauthorized();
});