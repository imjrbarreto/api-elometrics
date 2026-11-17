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
})->with([
    'coach',
    'student',
    'viewer',
]);

it('lets a member of any role view the workspace', function (string $role) {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $member = emMember($workspace, $role);

    Passport::actingAs($member);

    $this->getJson(apiUrl("workspaces/{$workspace->id}"))
        ->assertOk()
        ->assertJsonPath('data.id', $workspace->id);
})->with([
    'coach',
    'student',
    'viewer',
]);

it('forbids a user who is not a member from viewing the workspace', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    Passport::actingAs(User::factory()->create());

    $this->getJson(apiUrl("workspaces/{$workspace->id}"))
        ->assertForbidden();
});