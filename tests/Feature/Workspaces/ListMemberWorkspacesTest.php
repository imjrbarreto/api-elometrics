<?php

use App\Models\User;
use App\Models\Workspace;
use Laravel\Passport\Passport;

it('lists workspaces where the user is a member', function (string $role) {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $member = emMember($workspace, $role);

    emWorkspace();

    Passport::actingAs($member);

    $this->getJson(apiUrl('workspaces'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'id' => $workspace->id,
            'title' => $workspace->title,
            'status' => $workspace->status,
        ]);
})->with(['coach', 'student', 'viewer']);

it('lets a member of any role view the workspace', function (string $role) {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $member = emMember($workspace, $role);

    Passport::actingAs($member);

    $this->getJson(apiUrl("workspaces/{$workspace->id}"))
        ->assertOk()
        ->assertJsonPath('data.id', $workspace->id);
})->with(['coach','student','viewer']);

it('forbids a user who is not a member from viewing the workspace', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    Passport::actingAs(User::factory()->create());

    $this->getJson(apiUrl("workspaces/{$workspace->id}"))
        ->assertForbidden();
});

it('includes the user role in each workspace', function (string $role) {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $member = emMember($workspace, $role);

    Passport::actingAs($member);

    $this->getJson(apiUrl('workspaces'))
        ->assertOk()
        ->assertJsonPath('data.0.id', $workspace->id)
        ->assertJsonPath('data.0.role', $role);
})->with(['coach', 'student', 'viewer']);

it('includes the owner role for the workspace owner', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    Passport::actingAs($owner);

    $this->getJson(apiUrl('workspaces'))
        ->assertOk()
        ->assertJsonPath('data.0.role', 'owner');
});

it('returns the role of the user in each workspace', function () {
    /** @var \Tests\TestCase $this */

    [$ownerA, $workspaceA] = emWorkspace();
    [$ownerB, $workspaceB] = emWorkspace();

    $user = emMember($workspaceA, 'coach');
    emMember($workspaceB, 'student', $user);

    Passport::actingAs($user);

    $roles = collect(
        $this->getJson(apiUrl('workspaces'))
            ->assertOk()
            ->json('data')
    )->pluck('role', 'id');

    expect($roles[$workspaceA->id])->toBe('coach')
        ->and($roles[$workspaceB->id])->toBe('student');
});