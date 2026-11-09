<?php

use App\Models\User;
use Database\Seeders\WorkspaceRoleSeeder;
use Laravel\Passport\Passport;

it('allows the owner to add a student to the workspace', function () {
    /** @var \Tests\TestCase $this */

    $this->seed(WorkspaceRoleSeeder::class);

    [$owner, $workspace] = emWorkspace();

    $student = User::factory()->create([
        'email' => 'alumno@example.com',
    ]);

    Passport::actingAs($owner);

    $response = $this->postJson(
        apiUrl("workspaces/{$workspace->id}/members"),
        [
            'email' => $student->email,
            'role' => 'student',
        ]
    );

    $response
        ->assertCreated()
        ->assertJsonPath('data.id', $student->id)
        ->assertJsonPath('data.role', 'student');

    $this->assertDatabaseHas('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $student->id,
    ]);

    expect($workspace->roleOf($student))->toBe('student');
});

it('rejects an existing member without changing their role', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $member = emMember($workspace, 'viewer');

    Passport::actingAs($owner);

    $this->postJson(
        apiUrl("workspaces/{$workspace->id}/members"),
        [
            'email' => $member->email,
            'role' => 'student',
        ]
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    expect(
        $workspace->members()
            ->whereKey($member->id)
            ->count()
    )->toBe(1);

    expect($workspace->roleOf($member))->toBe('viewer');
});

it('forbids students and viewers from adding members', function (string $role) {
    /** @var \Tests\TestCase $this */

    [, $workspace] = emWorkspace();

    $actor = emMember($workspace, $role);

    $newMember = User::factory()->create();

    Passport::actingAs($actor);

    $this->postJson(
        apiUrl("workspaces/{$workspace->id}/members"),
        [
            'email' => $newMember->email,
            'role' => 'student',
        ]
    )->assertForbidden();

    $this->assertDatabaseMissing('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $newMember->id,
    ]);
})->with(['student', 'viewer']);

it('allows a coach to add a student to the workspace', function () {
    /** @var \Tests\TestCase $this */

    [, $workspace] = emWorkspace();

    $coach = emMember($workspace, 'coach');

    $student = User::factory()->create();

    Passport::actingAs($coach);

    $this->postJson(
        apiUrl("workspaces/{$workspace->id}/members"),
        [
            'email' => $student->email,
            'role' => 'student',
        ]
    )
        ->assertCreated()
        ->assertJsonPath('data.id', $student->id)
        ->assertJsonPath('data.role', 'student');

    $this->assertDatabaseHas('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $student->id,
    ]);

    expect($workspace->roleOf($student))->toBe('student');
});

it('forbids a coach from another workspace from adding members', function () {
    /** @var \Tests\TestCase $this */

    // Workspace al que el coach no pertenece.
    [, $workspace] = emWorkspace();

    // Otro workspace donde sí tiene el rol coach.
    [, $otherWorkspace] = emWorkspace();

    $coach = emMember($otherWorkspace, 'coach');

    $newMember = User::factory()->create();

    Passport::actingAs($coach);

    $this->postJson(
        apiUrl("workspaces/{$workspace->id}/members"),
        [
            'email' => $newMember->email,
            'role' => 'student',
        ]
    )->assertForbidden();

    $this->assertDatabaseMissing('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $newMember->id,
    ]);
});

it('rejects an invalid member email', function (array $data) {
    /** @var \Tests\TestCase $this */

    $this->seed(WorkspaceRoleSeeder::class);

    [$owner, $workspace] = emWorkspace();

    Passport::actingAs($owner);

    $this->postJson(
        apiUrl("workspaces/{$workspace->id}/members"),
        $data
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    expect($workspace->members()->count())->toBe(0);
})->with([
    'missing email' => [
        ['role' => 'student'],
    ],

    'invalid email format' => [
        [
            'email' => 'esto-no-es-un-email',
            'role' => 'student',
        ],
    ],

    'unregistered email' => [
        [
            'email' => 'no-registrado@example.com',
            'role' => 'student',
        ],
    ],
]);

it('rejects an invalid member role', function (array $roleData) {
    /** @var \Tests\TestCase $this */

    $this->seed(WorkspaceRoleSeeder::class);

    [$owner, $workspace] = emWorkspace();

    $newMember = User::factory()->create();

    Passport::actingAs($owner);

    $this->postJson(
        apiUrl("workspaces/{$workspace->id}/members"),
        [
            'email' => $newMember->email,
            ...$roleData,
        ]
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('role');

    $this->assertDatabaseMissing('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $newMember->id,
    ]);
})->with([
    'missing role' => [[]],
    'unknown role' => [['role' => 'admin']],
    'owner role' => [['role' => 'owner']],
]);

it('requires authentication to add a workspace member', function () {
    /** @var \Tests\TestCase $this */

    [, $workspace] = emWorkspace();

    $newMember = User::factory()->create();

    $this->postJson(
        apiUrl("workspaces/{$workspace->id}/members"),
        [
            'email' => $newMember->email,
            'role' => 'student',
        ]
    )->assertUnauthorized();

    $this->assertDatabaseMissing('user_workspace', [
        'workspace_id' => $workspace->id,
        'user_id' => $newMember->id,
    ]);
});