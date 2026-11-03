<?php

use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

it('requires authentication to list training sessions', function () {
    /** @var \Tests\TestCase $this */

    $this->getJson(apiUrl('training-sessions'))
        ->assertUnauthorized();
});

it('can list training sesison', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    $trainingSession = TrainingSession::Factory()->create([
        'user_id' => $user->id,
    ]);

    Passport::actingAs($user);

        $this->getJson(apiUrl('training-sessions'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $trainingSession->id);
});