<?php

namespace App\Http\Requests\Api\V1\Messaging;

use App\Http\Requests\Api\V1\ApiRequest;

class BlockUserRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'blocked_public_id' => ['sometimes', 'string', 'regex:/^[0-9a-fA-F]{32}$/'],
        ];
    }
}
