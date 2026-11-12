<?php

use App\Models\TrainingSession;
use Laravel\Passport\Passport;

it('allows the owner to list linked training sessions', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    // Sesión vinculada al workspace.
    $linkedSession = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($linkedSession->id);

    // Otra sesión del mismo creador, pero sin vincular.
    TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    Passport::actingAs($owner);

    $this->getJson(apiUrl("workspaces/{$workspace->id}/training-sessions"))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $linkedSession->id);
});