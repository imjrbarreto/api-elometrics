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

it('allows a viewer to list workspace members', function () {
    /** @var \Tests\TestCase $this */

    [, $workspace] = emWorkspace();

    $viewer = emMember($workspace, 'viewer');

    Passport::actingAs($viewer);

    $this->getJson(
        apiUrl("workspaces/{$workspace->id}/members")
    )->assertOk();
});

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