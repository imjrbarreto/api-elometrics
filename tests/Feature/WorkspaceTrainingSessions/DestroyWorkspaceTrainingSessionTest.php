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

    // Se elimina la vinculación.
    $this->assertDatabaseMissing('training_session_workspace', [
        'workspace_id' => $workspace->id,
        'training_session_id' => $session->id,
    ]);

    // La sesión original permanece.
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

    // La misma sesión está vinculada a ambos workspaces.
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

    // El otro vínculo permanece.
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

    // La sesión y su creador permanecen.
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

    // La vinculación debe permanecer.
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

    // En este workspace solo es viewer.
    $actor = emMember($workspace, 'viewer');

    // En otro workspace sí es coach.
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

    // El vínculo y la sesión permanecen.
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

    // Está vinculada únicamente al otro workspace.
    $otherWorkspace->trainingSessions()->attach($session->id);

    Passport::actingAs($owner);

    $this->deleteJson(
        apiUrl(
            "workspaces/{$workspace->id}/training-sessions/{$session->id}"
        )
    )->assertNotFound();

    // No debe afectar al vínculo existente.
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

    // Petición sin autenticar.
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