<?php

use App\Models\TrainingSession;
use Laravel\Passport\Passport;

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