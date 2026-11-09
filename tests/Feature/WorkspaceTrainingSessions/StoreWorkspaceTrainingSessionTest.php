<?php

use App\Models\TrainingSession;
use App\Models\Workspace;
use Laravel\Passport\Passport;
use App\Models\User;
use App\Support\WorkspaceRoles;

it('allows the owner to attach an own session to the workspace', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    Passport::actingAs($owner);

    $this->postJson(
        apiUrl("workspaces/{$workspace->id}/training-sessions"),
        [
            'training_session_id' => $session->id,
        ]
    )->assertNoContent();

    $this->assertDatabaseHas('training_session_workspace', [
        'workspace_id' => $workspace->id,
        'training_session_id' => $session->id,
    ]);

    $this->assertDatabaseCount('training_sessions', 1);

    $this->assertDatabaseHas('training_sessions', [
        'id' => $session->id,
        'user_id' => $owner->id,
    ]);
});

it('allows the owner to attach the same session to multiple workspaces', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $otherWorkspace = Workspace::factory()->create([
        'user_id' => $owner->id,
    ]);

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    Passport::actingAs($owner);

    foreach ([$workspace, $otherWorkspace] as $target) {
        $this->postJson(
            apiUrl("workspaces/{$target->id}/training-sessions"),
            [
                'training_session_id' => $session->id,
            ]
        )->assertNoContent();
    }

    foreach ([$workspace, $otherWorkspace] as $target) {
        $this->assertDatabaseHas('training_session_workspace', [
            'workspace_id' => $target->id,
            'training_session_id' => $session->id,
        ]);
    }

    $this->assertDatabaseCount('training_sessions', 1);
});

it('does not duplicate an existing session link', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($session->id);

    Passport::actingAs($owner);

    $this->postJson(
        apiUrl("workspaces/{$workspace->id}/training-sessions"),
        [
            'training_session_id' => $session->id,
        ]
    )->assertNoContent();

    expect(
        $workspace->trainingSessions()
            ->whereKey($session->id)
            ->count()
    )->toBe(1);

    $this->assertDatabaseCount('training_sessions', 1);
});

it('forbids attaching a session created by another user', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $otherSession = TrainingSession::factory()->create();

    Passport::actingAs($owner);

    $this->postJson(
        apiUrl("workspaces/{$workspace->id}/training-sessions"),
        [
            'training_session_id' => $otherSession->id,
        ]
    )->assertForbidden();

    $this->assertDatabaseMissing('training_session_workspace', [
        'workspace_id' => $workspace->id,
        'training_session_id' => $otherSession->id,
    ]);

    $this->assertDatabaseHas('training_sessions', [
        'id' => $otherSession->id,
        'user_id' => $otherSession->user_id,
    ]);
});

it('forbids non owners from attaching their own sessions', function (
    string $role
) {
    /** @var \Tests\TestCase $this */

    [, $workspace] = emWorkspace();

    $member = emMember($workspace, $role);

    $session = TrainingSession::factory()->create([
        'user_id' => $member->id,
    ]);

    Passport::actingAs($member);

    $this->postJson(
        apiUrl("workspaces/{$workspace->id}/training-sessions"),
        [
            'training_session_id' => $session->id,
        ]
    )->assertForbidden();

    $this->assertDatabaseMissing('training_session_workspace', [
        'workspace_id' => $workspace->id,
        'training_session_id' => $session->id,
    ]);
})->with(['student', 'viewer']);

it('allows a coach to attach an own session to the workspace', function () {
    /** @var \Tests\TestCase $this */

    [, $workspace] = emWorkspace();

    $coach = emMember($workspace, 'coach');

    $session = TrainingSession::factory()->create([
        'user_id' => $coach->id,
    ]);

    Passport::actingAs($coach);

    $this->postJson(
        apiUrl("workspaces/{$workspace->id}/training-sessions"),
        [
            'training_session_id' => $session->id,
        ]
    )->assertNoContent();

    $this->assertDatabaseHas('training_session_workspace', [
        'workspace_id' => $workspace->id,
        'training_session_id' => $session->id,
    ]);
});

it('does not reuse a coach role from another workspace when linking sessions', function () {
    /** @var \Tests\TestCase $this */

    [, $workspace] = emWorkspace();

    $actor = emMember($workspace, 'viewer');

    [, $otherWorkspace] = emWorkspace();

    $otherWorkspace->members()->attach($actor->id);

    WorkspaceRoles::within(
        $otherWorkspace,
        $actor,
        fn (User $member) => $member->syncRoles(['coach'])
    );

    $session = TrainingSession::factory()->create([
        'user_id' => $actor->id,
    ]);

    Passport::actingAs($actor);

    $this->postJson(
        apiUrl("workspaces/{$workspace->id}/training-sessions"),
        [
            'training_session_id' => $session->id,
        ]
    )->assertForbidden();

    $this->assertDatabaseMissing('training_session_workspace', [
        'workspace_id' => $workspace->id,
        'training_session_id' => $session->id,
    ]);
});

it('rejects an invalid training session identifier', function (array $data) {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    Passport::actingAs($owner);

    $this->postJson(
        apiUrl("workspaces/{$workspace->id}/training-sessions"),
        $data
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('training_session_id');

    // Ninguna petición inválida debe crear vinculaciones.
    $this->assertDatabaseCount('training_session_workspace', 0);
})->with([
    'missing identifier' => [
        [],
    ],

    'invalid identifier' => [
        ['training_session_id' => 'invalid'],
    ],

    'nonexistent session' => [
        ['training_session_id' => 999],
    ],
]);

it('requires authentication to attach a training session', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $this->postJson(
        apiUrl("workspaces/{$workspace->id}/training-sessions"),
        [
            'training_session_id' => $session->id,
        ]
    )->assertUnauthorized();

    $this->assertDatabaseMissing('training_session_workspace', [
        'workspace_id' => $workspace->id,
        'training_session_id' => $session->id,
    ]);

    $this->assertDatabaseHas('training_sessions', [
        'id' => $session->id,
        'user_id' => $owner->id,
    ]);
});