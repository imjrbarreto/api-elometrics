<?php

use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;

it('allows the owner to list workspace members', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $student = emMember($workspace, 'student');

    Passport::actingAs($owner);

    $response = $this->getJson(
        apiUrl("workspaces/{$workspace->id}/members")
    );

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'id' => $owner->id,
            'role' => 'owner',
        ])
        ->assertJsonFragment([
            'id' => $student->id,
            'role' => 'student',
        ]);
});

it('forbids a coach from another workspace from listing members', function () {
    /** @var \Tests\TestCase $this */

    [, $workspace] = emWorkspace();

    [, $otherWorkspace] = emWorkspace();

    $coach = emMember($otherWorkspace, 'coach');

    Passport::actingAs($coach);

    $this->getJson(
        apiUrl("workspaces/{$workspace->id}/members")
    )->assertForbidden();
});

it('allows members with viewing permission to list workspace members', function (string $role) {
    /** @var \Tests\TestCase $this */

    [, $workspace] = emWorkspace();

    $member = emMember($workspace, $role);

    Passport::actingAs($member);

    $this->getJson(
        apiUrl("workspaces/{$workspace->id}/members")
    )
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'id' => $member->id,
            'role' => $role,
        ]);
})->with(['student', 'viewer', 'coach']);

it('forbids a member without permission from listing members', function () {
    /** @var \Tests\TestCase $this */

    [, $workspace] = emWorkspace();

    $viewer = emMember($workspace, 'viewer');

    $viewerRole = Role::query()
        ->where('name', 'viewer')
        ->where('guard_name', 'api')
        ->whereNull('workspace_id')
        ->firstOrFail();

    $viewerRole->revokePermissionTo('members.view');

    $this->assertDatabaseHas('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $viewer->id,
    ]);

    Passport::actingAs($viewer);

    $this->getJson(
        apiUrl("workspaces/{$workspace->id}/members")
    )->assertForbidden();
});

it('requires authentication to list workspace members', function () {
    /** @var \Tests\TestCase $this */

    [, $workspace] = emWorkspace();

    $this->getJson(
        apiUrl("workspaces/{$workspace->id}/members")
    )->assertUnauthorized();
});

it('does not include members from another workspace', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $student = emMember($workspace, 'student');

    [$otherOwner, $otherWorkspace] = emWorkspace();

    $otherStudent = emMember($otherWorkspace, 'student');

    Passport::actingAs($owner);

    $response = $this->getJson(
        apiUrl("workspaces/{$workspace->id}/members")
    );

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data');

    expect(array_column($response->json('data'), 'id'))
        ->toContain($owner->id, $student->id)
        ->not->toContain($otherOwner->id, $otherStudent->id);
});