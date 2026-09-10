<?php

namespace App\Http\Requests\Api\V1\Messaging;

use App\Http\Requests\Api\V1\ApiRequest;

class CreateConversationRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'participant_public_id' => ['required', 'string', 'regex:/^[0-9a-fA-F]{32}$/'],
        ];
    }
}
