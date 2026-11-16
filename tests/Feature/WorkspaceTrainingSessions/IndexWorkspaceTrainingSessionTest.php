<?php

use App\Models\TrainingSession;
use Laravel\Passport\Passport;

it('allows the owner to list linked training sessions', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $linkedSession = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($linkedSession->id);

    TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    Passport::actingAs($owner);

    $this->getJson(apiUrl("workspaces/{$workspace->id}/training-sessions"))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $linkedSession->id);
});

it('allows members to list linked training sessions', function (
    string $role
) {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $member = emMember($workspace, $role);

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($session->id);

    Passport::actingAs($member);

    $this->getJson(
        apiUrl("workspaces/{$workspace->id}/training-sessions")
    )
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $session->id);
})->with(['coach', 'student', 'viewer']);

it('forbids a coach from another workspace from listing sessions', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($session->id);

    [, $otherWorkspace] = emWorkspace();

    $coach = emMember($otherWorkspace, 'coach');

    Passport::actingAs($coach);

    $this->getJson(
        apiUrl("workspaces/{$workspace->id}/training-sessions")
    )->assertForbidden();
});

it('returns an empty collection when no sessions are linked', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    Passport::actingAs($owner);

    $this->getJson(
        apiUrl("workspaces/{$workspace->id}/training-sessions")
    )
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('requires authentication to list linked training sessions', function () {
    /** @var \Tests\TestCase $this */

    [, $workspace] = emWorkspace();

    $this->getJson(
        apiUrl("workspaces/{$workspace->id}/training-sessions")
    )->assertUnauthorized();
});

it('returns the session resource as a link', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $session = \App\Models\TrainingSession::factory()->create([
        'user_id' => $owner->id,
        'resource' => 'https://lichess.org/study',
    ]);

    $workspace->trainingSessions()->attach($session->id);

    \Laravel\Passport\Passport::actingAs($owner);

    $this->getJson(
        apiUrl("workspaces/{$workspace->id}/training-sessions")
    )
        ->assertOk()
        ->assertJsonPath('data.0.resource', 'https://lichess.org/study');
});