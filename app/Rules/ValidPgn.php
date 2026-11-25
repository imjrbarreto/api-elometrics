<?php

namespace App\Rules;

use Closure;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;
use Onspli\Chess\PGN;

class ValidPgn implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $pgn = new PGN($value);

            $pgn->get_current_fen();
        } catch (Exception) {
            $fail('The :attribute must contain a valid PGN.');
        }
    }
}