<?php

use App\Support\PgnPdfFormatter;

it('does not require a diagram for a standard game', function () {
    $game = (new PgnPdfFormatter())->format(
        '1. e4 e5 2. Nf3 Nc6 *'
    );

    expect($game['has_diagram'])->toBeFalse();
});

it('requires a diagram for a custom initial position', function () {
    $pgn = <<<'PGN'
    [SetUp "1"]
    [FEN "8/4k3/8/8/4P3/8/4K3/8 b - - 0 1"]

    1... Ke6 *
    PGN;

    $game = (new PgnPdfFormatter())->format($pgn);

    expect($game['has_diagram'])->toBeTrue()
        ->and($game['turn'])->toBe('Negras');
});

it('extracts player names from a pgn', function () {
    $pgn = <<<'PGN'
    [White "Carlsen"]
    [Black "Caruana"]

    1. e4 e5 *
    PGN;

    $game = (new PgnPdfFormatter())->format($pgn);

    expect($game['white'])->toBe('Carlsen')
        ->and($game['black'])->toBe('Caruana');
});

it('builds a chessboard from a custom initial position', function () {
    $pgn = <<<'PGN'
    [SetUp "1"]
    [FEN "8/4k3/8/8/4P3/8/4K3/8 b - - 0 1"]

    1... Ke6 *
    PGN;

    $game = (new PgnPdfFormatter())->format($pgn);

    expect($game['board'])->toHaveCount(8)
        ->and($game['board'][0])->toHaveCount(8)
        ->and($game['board'][1][4])->toBe('♚')
        ->and($game['board'][4][4])->toBe('♙')
        ->and($game['board'][6][4])->toBe('♔');
});