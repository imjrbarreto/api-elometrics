<?php

use App\Models\Workspace;
use App\Models\TrainingSession;
use App\Models\User;
use App\Support\WorkspaceRoles;
use Laravel\Passport\Passport;

it('allows the owner to detach a session without deleting it', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($session->id);

    Passport::actingAs($owner);

    $this->deleteJson(
        apiUrl(
            "workspaces/{$workspace->id}/training-sessions/{$session->id}"
        )
    )->assertNoContent();

    $this->assertDatabaseMissing('training_session_workspace', [
        'workspace_id' => $workspace->id,
        'training_session_id' => $session->id,
    ]);

    $this->assertDatabaseHas('training_sessions', [
        'id' => $session->id,
        'user_id' => $owner->id,
    ]);
});

it('preserves links to other workspaces when detaching a session', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $otherWorkspace = Workspace::factory()->create([
        'user_id' => $owner->id,
    ]);

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($session->id);
    $otherWorkspace->trainingSessions()->attach($session->id);

    Passport::actingAs($owner);

    $this->deleteJson(
        apiUrl(
            "workspaces/{$workspace->id}/training-sessions/{$session->id}"
        )
    )->assertNoContent();

    $this->assertDatabaseMissing('training_session_workspace', [
        'workspace_id' => $workspace->id,
        'training_session_id' => $session->id,
    ]);

    $this->assertDatabaseHas('training_session_workspace', [
        'workspace_id' => $otherWorkspace->id,
        'training_session_id' => $session->id,
    ]);

    $this->assertDatabaseHas('training_sessions', [
        'id' => $session->id,
    ]);
});

it('allows a coach to detach a session created by the owner', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $coach = emMember($workspace, 'coach');

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($session->id);

    Passport::actingAs($coach);

    $this->deleteJson(
        apiUrl(
            "workspaces/{$workspace->id}/training-sessions/{$session->id}"
        )
    )->assertNoContent();

    $this->assertDatabaseMissing('training_session_workspace', [
        'workspace_id' => $workspace->id,
        'training_session_id' => $session->id,
    ]);

    $this->assertDatabaseHas('training_sessions', [
        'id' => $session->id,
        'user_id' => $owner->id,
    ]);
});

it('forbids students and viewers from detaching sessions', function (
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

    $this->deleteJson(
        apiUrl(
            "workspaces/{$workspace->id}/training-sessions/{$session->id}"
        )
    )->assertForbidden();

    $this->assertDatabaseHas('training_session_workspace', [
        'workspace_id' => $workspace->id,
        'training_session_id' => $session->id,
    ]);

    $this->assertDatabaseHas('training_sessions', [
        'id' => $session->id,
    ]);
})->with(['student', 'viewer']);

it('does not reuse a coach role from another workspace when detaching', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $actor = emMember($workspace, 'viewer');

    [, $otherWorkspace] = emWorkspace();

    $otherWorkspace->members()->attach($actor->id);

    WorkspaceRoles::within(
        $otherWorkspace,
        $actor,
        fn (User $member) => $member->syncRoles(['coach'])
    );

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($session->id);

    Passport::actingAs($actor);

    $this->deleteJson(
        apiUrl(
            "workspaces/{$workspace->id}/training-sessions/{$session->id}"
        )
    )->assertForbidden();

    $this->assertDatabaseHas('training_session_workspace', [
        'workspace_id' => $workspace->id,
        'training_session_id' => $session->id,
    ]);

    $this->assertDatabaseHas('training_sessions', [
        'id' => $session->id,
    ]);
});

it('returns not found when the session is linked only to another workspace', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $otherWorkspace = Workspace::factory()->create([
        'user_id' => $owner->id,
    ]);

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $otherWorkspace->trainingSessions()->attach($session->id);

    Passport::actingAs($owner);

    $this->deleteJson(
        apiUrl(
            "workspaces/{$workspace->id}/training-sessions/{$session->id}"
        )
    )->assertNotFound();

    $this->assertDatabaseHas('training_session_workspace', [
        'workspace_id' => $otherWorkspace->id,
        'training_session_id' => $session->id,
    ]);

    $this->assertDatabaseHas('training_sessions', [
        'id' => $session->id,
    ]);
});

it('requires authentication to detach a training session', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($session->id);

    $this->deleteJson(
        apiUrl(
            "workspaces/{$workspace->id}/training-sessions/{$session->id}"
        )
    )->assertUnauthorized();

    $this->assertDatabaseHas('training_session_workspace', [
        'workspace_id' => $workspace->id,
        'training_session_id' => $session->id,
    ]);

    $this->assertDatabaseHas('training_sessions', [
        'id' => $session->id,
    ]);
});