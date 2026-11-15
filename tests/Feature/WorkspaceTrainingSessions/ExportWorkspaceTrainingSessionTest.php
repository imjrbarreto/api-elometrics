<?php

use App\Models\TrainingSession;
use Laravel\Passport\Passport;

it('allows the owner to export a linked training session as pdf', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($session->id);

    Passport::actingAs($owner);

    $response = $this->getJson(
        apiUrl(
            "workspaces/{$workspace->id}/training-sessions/{$session->id}/export"
        )
    );

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertDownload("training-session-{$session->id}.pdf");

    expect($response->getContent())->toStartWith('%PDF-');
});