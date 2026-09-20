<?php

namespace App\Http\Requests\Api\V1\Account;

use App\Http\Requests\Api\V1\ApiRequest;

class VerifyEmailOtpRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'code' => ['required', 'digits:6'],
        ];
    }
}
