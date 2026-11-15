<?php

use App\Models\TrainingSession;
use App\Models\User;
use Laravel\Passport\Passport;
use App\Support\WorkspaceRoles;
use Spatie\Permission\Models\Role;
use Database\Seeders\WorkspaceRoleSeeder;
use Illuminate\Validation\ValidationException;

it('allows a student to respond to a linked session', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $student = emMember($workspace, 'student');

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($session->id);

    Passport::actingAs($student);

    $this->postJson(
        apiUrl(
            "workspaces/{$workspace->id}/training-sessions/{$session->id}/responses"
        ),
        [
            'content' => 'Mi variante principal es 1.Cf5.',
        ]
    )
        ->assertCreated()
        ->assertJsonPath('data.user_id', $student->id)
        ->assertJsonPath('data.workspace_id', $workspace->id)
        ->assertJsonPath('data.training_session_id', $session->id)
        ->assertJsonPath('data.content', 'Mi variante principal es 1.Cf5.');

    $this->assertDatabaseHas('training_session_responses', [
        'workspace_id' => $workspace->id,
        'training_session_id' => $session->id,
        'user_id' => $student->id,
        'content' => 'Mi variante principal es 1.Cf5.',
    ]);
});

it('forbids other roles from responding to a session', function (string $role) {
    /** @var \Tests\TestCase $this */

    $this->seed(WorkspaceRoleSeeder::class);

    [$owner, $workspace] = emWorkspace();

    $actor = $role === 'owner'
        ? $owner
        : emMember($workspace, $role);

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($session->id);

    Passport::actingAs($actor);

    $this->postJson(
        apiUrl(
            "workspaces/{$workspace->id}/training-sessions/{$session->id}/responses"
        ),
        [
            'content' => 'Una respuesta.',
        ]
    )->assertForbidden();

    $this->assertDatabaseCount('training_session_responses', 0);
})->with(['owner', 'coach', 'viewer']);

it('forbids a student without permission from responding', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $student = emMember($workspace, 'student');

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($session->id);

    $studentRole = Role::query()
        ->where('name', 'student')
        ->where('guard_name', 'api')
        ->whereNull('workspace_id')
        ->firstOrFail();

    $studentRole->revokePermissionTo('training-sessions.respond');

    Passport::actingAs($student);

    $this->postJson(
        apiUrl(
            "workspaces/{$workspace->id}/training-sessions/{$session->id}/responses"
        ),
        [
            'content' => 'Una respuesta.',
        ]
    )->assertForbidden();

    $this->assertDatabaseCount('training_session_responses', 0);
});

it('does not reuse responding permission from another workspace', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $actor = emMember($workspace, 'viewer');

    [, $otherWorkspace] = emWorkspace();

    $otherWorkspace->members()->attach($actor->id);

    WorkspaceRoles::within(
        $otherWorkspace,
        $actor,
        fn (User $member) => $member->syncRoles(['student'])
    );

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($session->id);

    Passport::actingAs($actor);

    $this->postJson(
        apiUrl(
            "workspaces/{$workspace->id}/training-sessions/{$session->id}/responses"
        ),
        [
            'content' => 'Una respuesta.',
        ]
    )->assertForbidden();

    $this->assertDatabaseCount('training_session_responses', 0);
});

it('returns not found when the session is not linked to the workspace', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $student = emMember($workspace, 'student');

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    Passport::actingAs($student);

    $this->postJson(
        apiUrl(
            "workspaces/{$workspace->id}/training-sessions/{$session->id}/responses"
        ),
        [
            'content' => 'Una respuesta.',
        ]
    )->assertNotFound();

    $this->assertDatabaseCount('training_session_responses', 0);

    $this->assertDatabaseHas('training_sessions', [
        'id' => $session->id,
        'user_id' => $owner->id,
    ]);
});

it('rejects invalid response content', function (array $data) {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $student = emMember($workspace, 'student');

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($session->id);

    Passport::actingAs($student);

    $this->postJson(
        apiUrl(
            "workspaces/{$workspace->id}/training-sessions/{$session->id}/responses"
        ),
        $data
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('content');

    $this->assertDatabaseCount('training_session_responses', 0);
})->with([
    'missing content' => [
        [],
    ],
    'empty content' => [
        ['content' => ''],
    ],
    'invalid content type' => [
        ['content' => ['not', 'text']],
    ],
    'content too long' => [
        ['content' => str_repeat('a', 10001)],
    ],
]);

it('rejects a second response in the same workspace and session', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $student = emMember($workspace, 'student');

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($session->id);

    Passport::actingAs($student);

    $url = apiUrl(
        "workspaces/{$workspace->id}/training-sessions/{$session->id}/responses"
    );

    $this->postJson($url, [
        'content' => 'Primera respuesta.',
    ])->assertCreated();

    $this->postJson($url, [
        'content' => 'Segunda respuesta.',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('content');

    $this->assertDatabaseCount('training_session_responses', 1);

    $this->assertDatabaseHas('training_session_responses', [
        'workspace_id' => $workspace->id,
        'training_session_id' => $session->id,
        'user_id' => $student->id,
        'content' => 'Primera respuesta.',
    ]);
});