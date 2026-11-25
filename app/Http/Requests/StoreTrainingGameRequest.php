<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrainingGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(
            'update',
            $this->route('training_session')
        );
    }

    public function rules(): array
    {
        return [
            'pgn' => ['required', 'string', 'max:50000'],
        ];
    }
}