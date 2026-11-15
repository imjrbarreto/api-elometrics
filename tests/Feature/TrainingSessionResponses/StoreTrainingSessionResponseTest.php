<?php

use App\Models\TrainingSession;
use App\Models\User;
use Laravel\Passport\Passport;
use App\Support\WorkspaceRoles;
use Spatie\Permission\Models\Role;
use Database\Seeders\WorkspaceRoleSeeder;

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