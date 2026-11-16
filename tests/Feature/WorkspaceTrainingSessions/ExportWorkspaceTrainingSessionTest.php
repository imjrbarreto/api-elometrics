<?php

use App\Models\Workspace;
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

it('allows workspace members to export a linked session as pdf', function (string $role) {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $member = emMember($workspace, $role);

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($session->id);

    Passport::actingAs($member);

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
})->with(['coach', 'student', 'viewer']);

it('forbids a coach from another workspace from exporting the session', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($session->id);

    [, $otherWorkspace] = emWorkspace();

    $coach = emMember($otherWorkspace, 'coach');

    Passport::actingAs($coach);

    $this->getJson(
        apiUrl(
            "workspaces/{$workspace->id}/training-sessions/{$session->id}/export"
        )
    )->assertForbidden();
});

it('returns not found when the session is linked only to another workspace', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $otherWorkspace = Workspace::factory()->create([
        'user_id' => $owner->id,
    ]);

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $otherWorkspace->trainingSessions()->attach($session->id);

    Passport::actingAs($owner);

    $this->getJson(
        apiUrl(
            "workspaces/{$workspace->id}/training-sessions/{$session->id}/export"
        )
    )->assertNotFound();
});