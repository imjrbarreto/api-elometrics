<?php

use App\Models\TrainingSession;
use Laravel\Passport\Passport;

it('allows the owner to list linked training sessions', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    // Sesión vinculada al workspace.
    $linkedSession = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($linkedSession->id);

    // Otra sesión del mismo creador, pero sin vincular.
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

    // El usuario pertenece únicamente al otro workspace.
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

    // El owner tiene una sesión, pero no está vinculada.
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