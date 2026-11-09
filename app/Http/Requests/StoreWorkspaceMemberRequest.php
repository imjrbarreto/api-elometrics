<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreWorkspaceMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('addMember', $this->route('workspace'));
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'exists:users,email',
            ],

            'role' => [
                'required',
                Rule::in(['coach', 'student', 'viewer']),
            ],
        ];
    }
}