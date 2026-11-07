<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

it('lists the owner and members of this workspace', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $student = emMember($workspace, 'student');

    [, $otherWorkspace] = emWorkspace();

    emMember($otherWorkspace, 'viewer');

    Passport::actingAs($student);

    $this->getJson(apiUrl("workspaces/{$workspace->id}/members"))
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

it('forbids outsiders from listing members', function () {
    /** @var \Tests\TestCase $this */

    [, $workspace] = emWorkspace();

    Passport::actingAs(User::factory()->create());

    $this->getJson(apiUrl("workspaces/{$workspace->id}/members"))
        ->assertForbidden();
});

it('requires authentication', function () {
    /** @var \Tests\TestCase $this */

    [, $workspace] = emWorkspace();

    $this->getJson(apiUrl("workspaces/{$workspace->id}/members"))
        ->assertUnauthorized();
});
