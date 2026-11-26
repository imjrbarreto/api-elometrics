<?php

use App\Models\TrainingGame;
use App\Models\Workspace;
use App\Models\TrainingSession;
use App\Support\PgnPdfFormatter;
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

it('requires authentication to export a linked training session', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $workspace->trainingSessions()->attach($session->id);

    $this->getJson(
        apiUrl(
            "workspaces/{$workspace->id}/training-sessions/{$session->id}/export"
        )
    )->assertUnauthorized();
});

it('includes training games in the pdf template', function () {
    /** @var \Tests\TestCase $this */

    [$owner, $workspace] = emWorkspace();

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    TrainingGame::factory()->create([
        'training_session_id' => $session->id,
        'pgn' => '1. e4 e5 2. Nf3 Nc6 *',
    ]);

    TrainingGame::factory()->create([
        'training_session_id' => $session->id,
        'pgn' => '1. d4 d5 2. c4 *',
    ]);

    $session->load(['user', 'games']);

    $formatter = new PgnPdfFormatter();

    $view = view('pdf.training-session', [
        'workspace' => $workspace,
        'session' => $session,
        'games' => $session->games->map(
            fn ($game) => $formatter->format($game->pgn)
        ),
    ]);

    expect($view->render())
        ->toContain('Partidas')
        ->toContain('1. e4 e5 2. Nf3 Nc6')
        ->toContain('1. d4 d5 2. c4');
});

it('does not render a diagram for a standard chess game', function () {
    [$owner, $workspace] = emWorkspace();

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    TrainingGame::factory()->create([
        'training_session_id' => $session->id,
        'pgn' => '1. e4 e5 2. Nf3 Nc6 *',
    ]);

    $session->load(['user', 'games']);

    $formatter = new PgnPdfFormatter();

    $html = view('pdf.training-session', [
        'workspace' => $workspace,
        'session' => $session,
        'games' => $session->games->map(
            fn ($game) => $formatter->format($game->pgn)
        ),
    ])->render();

    expect($html)
        ->toContain('1. e4 e5 2. Nf3 Nc6')
        ->not->toContain('<table class="chessboard">');
});

it('renders a diagram for a custom initial position', function () {
    [$owner, $workspace] = emWorkspace();

    $session = TrainingSession::factory()->create([
        'user_id' => $owner->id,
    ]);

    $pgn = <<<'PGN'
    [SetUp "1"]
    [FEN "8/4k3/8/8/4P3/8/4K3/8 b - - 0 1"]

    1... Ke6 *
    PGN;

    TrainingGame::factory()->create([
        'training_session_id' => $session->id,
        'pgn' => $pgn,
    ]);

    $session->load(['user', 'games']);

    $formatter = new PgnPdfFormatter();

    $html = view('pdf.training-session', [
        'workspace' => $workspace,
        'session' => $session,
        'games' => $session->games->map(
            fn ($game) => $formatter->format($game->pgn)
        ),
    ])->render();

    expect($html)
        ->toContain('<table class="chessboard">')
        ->toContain('Negras juegan')
        ->toContain('♚')
        ->toContain('♔')
        ->toContain('♙')
        ->not->toContain('[FEN');
});