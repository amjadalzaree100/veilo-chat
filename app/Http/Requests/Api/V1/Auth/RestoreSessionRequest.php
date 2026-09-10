<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RestoreSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_identifier' => ['required', 'string', 'max:255', 'regex:/\S/'],
            'device_secret' => ['required', 'string', 'min:64', 'max:255'],
        ];
    }
}
