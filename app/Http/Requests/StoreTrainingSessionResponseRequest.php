<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreTrainingSessionResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows(
            'respondToTrainingSession',
            $this->route('workspace')
        );
    }

    public function rules(): array
    {
        return [
            'content' => [
                'required',
                'string',
                'max:10000',
            ],
        ];
    }
}