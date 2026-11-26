<?php

namespace App\Rules;

use App\Support\PgnMainline;
use Closure;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;
use Onspli\Chess\PGN;

class ValidPgn implements ValidationRule
{
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail
    ): void {
        try {
            $mainline = PgnMainline::extract($value);

            $pgn = new PGN($mainline);

            $pgn->get_current_fen();
        } catch (Exception) {
            $fail('The :attribute must contain a valid PGN mainline.');
        }
    }
}