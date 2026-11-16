<?php

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