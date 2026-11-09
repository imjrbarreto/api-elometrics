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