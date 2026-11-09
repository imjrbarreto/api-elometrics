<?php

use App\Models\TrainingSession;
use App\Models\Workspace;
use Laravel\Passport\Passport;

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