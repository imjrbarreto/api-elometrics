<?php

namespace App\Support;

use InvalidArgumentException;

class PgnMainline
{
    public static function extract(string $pgn): string
    {
        $result = '';
        $depth = 0;
        $inComment = false;
        $inLineComment = false;
        $inTag = false;
        $inQuote = false;
        $escaped = false;

        foreach (str_split($pgn) as $char) {
            if ($inTag) {
                $result .= $char;

                if ($escaped) {
                    $escaped = false;
                    continue;
                }

                if ($char === '\\' && $inQuote) {
                    $escaped = true;
                } elseif ($char === '"') {
                    $inQuote = ! $inQuote;
                } elseif ($char === ']' && ! $inQuote) {
                    $inTag = false;
                }

                continue;
            }

            if ($inComment) {
                if ($char === '}') {
                    $inComment = false;
                }

                continue;
            }

            if ($inLineComment) {
                if ($char === "\n" || $char === "\r") {
                    $inLineComment = false;
                }

                continue;
            }

            if ($char === '{') {
                $inComment = true;

                if ($depth === 0) {
                    $result .= ' ';
                }

                continue;
            }

            if ($char === ';') {
                $inLineComment = true;

                if ($depth === 0) {
                    $result .= ' ';
                }

                continue;
            }

            if ($char === '(') {
                $depth++;

                if ($depth === 1) {
                    $result .= ' ';
                }

                continue;
            }

            if ($char === ')') {
                if ($depth === 0) {
                    throw new InvalidArgumentException('Unexpected closing parenthesis.');
                }

                $depth--;
                continue;
            }

            if ($depth === 0) {
                if ($char === '[') {
                    $inTag = true;
                }

                $result .= $char;
            }
        }

        if ($depth !== 0 || $inComment || $inTag) {
            throw new InvalidArgumentException('Unclosed PGN structure.');
        }

        return trim($result);
    }
}