<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[A-Za-z0-9_]+$/',
            ],
            'display_name' => ['nullable', 'string', 'max:100'],
            'device_identifier' => ['required', 'string', 'max:255', 'regex:/\S/'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'platform' => ['required', Rule::in(['ios', 'android', 'web', 'desktop'])],
            'push_token' => ['nullable', 'string', 'max:10000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->username)) {
            $this->merge(['username' => strtolower(trim($this->username))]);
        }
    }
}
