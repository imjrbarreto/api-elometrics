<?php

use App\Models\TrainingSession;
use Laravel\Passport\Passport;

it('allows the owner to detach a session without deleting it', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($session->id);

    Passport::actingAs($owner);

    $this->deleteJson(
        apiUrl(
            "workspaces/{$workspace->id}/training-sessions/{$session->id}"
        )
    )->assertNoContent();

    // Se elimina la vinculación.
    $this->assertDatabaseMissing('training_session_workspace', [
        'workspace_id' => $workspace->id,
        'training_session_id' => $session->id,
    ]);

    // La sesión original permanece.
    $this->assertDatabaseHas('training_sessions', [
        'id' => $session->id,
        'user_id' => $owner->id,
    ]);
});