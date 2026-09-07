<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecoveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'public_id' => ['required', 'string', 'regex:/^[0-9a-fA-F]{32}$/'],
            'recovery_secret' => ['required', 'string', 'min:32', 'max:200'],
            'device_identifier' => ['required', 'string', 'max:255', 'regex:/\S/'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'platform' => ['required', Rule::in(['ios', 'android', 'web', 'desktop'])],
            'push_token' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
