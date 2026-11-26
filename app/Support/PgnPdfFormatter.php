<?php

namespace App\Support;

use Onspli\Chess\PGN;

class PgnPdfFormatter
{
    private const STANDARD_POSITION =
        'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -';

    public function format(string $content): array
    {
        $pgn = new PGN(PgnMainline::extract($content));

        $fen = $pgn->get_initial_fen();
        $tags = $pgn->get_tags();

        $fields = explode(' ', $fen);

        $hasDiagram = implode(' ', array_slice($fields, 0, 4))
            !== self::STANDARD_POSITION;

        return [
            'board' => $hasDiagram ? $this->buildBoard($fen) : null,
            'white' => $tags['White'] ?? null,
            'black' => $tags['Black'] ?? null,
            'notation' => $pgn->export_movetext(),
            'fen' => $fen,
            'has_diagram' => $hasDiagram,
            'turn' => $fields[1] === 'w' ? 'Blancas' : 'Negras',
        ];
    }

    private function buildBoard(string $fen): array
    {
        $placement = explode(' ', $fen)[0];

        $pieces = [
            'K' => '♔',
            'Q' => '♕',
            'R' => '♖',
            'B' => '♗',
            'N' => '♘',
            'P' => '♙',
            'k' => '♚',
            'q' => '♛',
            'r' => '♜',
            'b' => '♝',
            'n' => '♞',
            'p' => '♟',
        ];

        $board = [];

        foreach (explode('/', $placement) as $rank) {
            $row = [];

            foreach (str_split($rank) as $char) {
                if (ctype_digit($char)) {
                    $row = array_merge(
                        $row,
                        array_fill(0, (int) $char, '')
                    );
                } else {
                    $row[] = $pieces[$char];
                }
            }

            $board[] = $row;
        }

        return $board;
    }
}