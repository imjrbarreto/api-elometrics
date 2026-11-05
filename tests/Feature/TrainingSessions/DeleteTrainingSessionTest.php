<?php

use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

it('can delete a training session', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    $trainingSession = TrainingSession::factory()->create([
        'user_id' => $user->id,
    ]);

    Passport::actingAs($user);

    $this->deleteJson(apiUrl("training-sessions/{$trainingSession->id}"))
        ->assertNoContent();

    $this->assertDatabaseMissing('training_sessions', [
        'id' => $trainingSession->id,
    ]);
});
