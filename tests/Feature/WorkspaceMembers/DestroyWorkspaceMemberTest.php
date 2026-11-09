<?php

use Laravel\Passport\Passport;
use App\Models\User;
use App\Support\WorkspaceRoles;

it('allows the owner to remove a workspace member', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $student = emMember($workspace, 'student');

    Passport::actingAs($owner);

    $this->deleteJson(
        apiUrl("workspaces/{$workspace->id}/members/{$student->id}")
    )->assertNoContent();

    $this->assertDatabaseMissing('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $student->id,
    ]);

    $this->assertDatabaseMissing('model_has_roles', [
        'workspace_id' => $workspace->id,
        'model_id' => $student->id,
        'model_type' => $student->getMorphClass(),
    ]);

    $this->assertDatabaseHas('users', [
        'id' => $student->id,
    ]);
});

it('preserves membership and roles in other workspaces', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $student = emMember($workspace, 'student');

    [, $otherWorkspace] = emWorkspace();

    $otherWorkspace->members()->attach($student->id);

    WorkspaceRoles::within(
        $otherWorkspace,
        $student,
        fn (User $member) => $member->syncRoles(['coach'])
    );

    Passport::actingAs($owner);

    $this->deleteJson(
        apiUrl("workspaces/{$workspace->id}/members/{$student->id}")
    )->assertNoContent();

    $this->assertDatabaseMissing('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $student->id,
    ]);

    $this->assertDatabaseHas('user_workspace', [
        'workspace_id' => $otherWorkspace->id,
        'user_id' => $student->id,
    ]);

    expect($otherWorkspace->roleOf($student))->toBe('coach');
});

it('allows a member to leave the workspace', function (string $role) {
    /** @var \Tests\TestCase $this */

    [, $workspace] = emWorkspace();

    $member = emMember($workspace, $role);

    Passport::actingAs($member);

    $this->deleteJson(
        apiUrl("workspaces/{$workspace->id}/members/{$member->id}")
    )->assertNoContent();

    $this->assertDatabaseMissing('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $member->id,
    ]);

    $this->assertDatabaseMissing('model_has_roles', [
        'workspace_id' => $workspace->id,
        'model_id' => $member->id,
        'model_type' => $member->getMorphClass(),
    ]);

    $this->assertDatabaseHas('users', [
        'id' => $member->id,
    ]);
})->with(['student', 'viewer', 'coach']);

it('prevents the owner from leaving the workspace', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    Passport::actingAs($owner);

    $this->deleteJson(
        apiUrl("workspaces/{$workspace->id}/members/{$owner->id}")
    )->assertForbidden();

    $this->assertDatabaseHas('workspaces', [
        'id' => $workspace->id,
        'user_id' => $owner->id,
    ]);

    $this->assertDatabaseHas('users', [
        'id' => $owner->id,
    ]);
});

it('prevents a coach from removing the owner', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $coach = emMember($workspace, 'coach');

    Passport::actingAs($coach);

    $this->deleteJson(
        apiUrl("workspaces/{$workspace->id}/members/{$owner->id}")
    )->assertForbidden();

    $this->assertDatabaseHas('workspaces', [
        'id' => $workspace->id,
        'user_id' => $owner->id,
    ]);
});

it('forbids students and viewers from removing other members', function (
    string $role
) {
    /** @var \Tests\TestCase $this */

    [, $workspace] = emWorkspace();

    $actor = emMember($workspace, $role);
    $target = emMember($workspace, 'student');

    Passport::actingAs($actor);

    $this->deleteJson(
        apiUrl("workspaces/{$workspace->id}/members/{$target->id}")
    )->assertForbidden();

    $this->assertDatabaseHas('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $target->id,
    ]);

    expect($workspace->roleOf($target))->toBe('student');
})->with(['student', 'viewer']);

it('allows a coach to remove another member', function () {
    /** @var \Tests\TestCase $this */

    [, $workspace] = emWorkspace();

    $coach = emMember($workspace, 'coach');
    $student = emMember($workspace, 'student');

    Passport::actingAs($coach);

    $this->deleteJson(
        apiUrl("workspaces/{$workspace->id}/members/{$student->id}")
    )->assertNoContent();

    $this->assertDatabaseMissing('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $student->id,
    ]);

    $this->assertDatabaseMissing('model_has_roles', [
        'workspace_id' => $workspace->id,
        'model_id' => $student->id,
        'model_type' => $student->getMorphClass(),
    ]);

    $this->assertDatabaseHas('users', [
        'id' => $student->id,
    ]);

    $this->assertDatabaseHas('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $coach->id,
    ]);

    expect($workspace->roleOf($coach))->toBe('coach');
});

it('does not reuse removal permissions from another workspace', function () {
    /** @var \Tests\TestCase $this */

    [, $workspace] = emWorkspace();

    $actor = emMember($workspace, 'viewer');
    $target = emMember($workspace, 'student');

    [, $otherWorkspace] = emWorkspace();

    $otherWorkspace->members()->attach($actor->id);

    WorkspaceRoles::within(
        $otherWorkspace,
        $actor,
        fn (User $member) => $member->syncRoles(['coach'])
    );

    Passport::actingAs($actor);

    $this->deleteJson(
        apiUrl("workspaces/{$workspace->id}/members/{$target->id}")
    )->assertForbidden();

    $this->assertDatabaseHas('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $target->id,
    ]);

    expect($workspace->roleOf($target))->toBe('student');

    expect($workspace->roleOf($actor))->toBe('viewer');
    expect($otherWorkspace->roleOf($actor))->toBe('coach');
});

it('returns not found when the target is not a workspace member', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $outsider = User::factory()->create();

    Passport::actingAs($owner);

    $this->deleteJson(
        apiUrl("workspaces/{$workspace->id}/members/{$outsider->id}")
    )->assertNotFound();

    $this->assertDatabaseHas('users', [
        'id' => $outsider->id,
    ]);

    $this->assertDatabaseMissing('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $outsider->id,
    ]);
});

it('requires authentication to remove a workspace member', function () {
    /** @var \Tests\TestCase $this */

    [, $workspace] = emWorkspace();

    $student = emMember($workspace, 'student');

    $this->deleteJson(
        apiUrl("workspaces/{$workspace->id}/members/{$student->id}")
    )->assertUnauthorized();

    $this->assertDatabaseHas('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $student->id,
    ]);

    expect($workspace->roleOf($student))->toBe('student');

    $this->assertDatabaseHas('users', [
        'id' => $student->id,
    ]);
});