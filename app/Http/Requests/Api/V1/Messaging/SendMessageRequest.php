<?php

namespace App\Http\Requests\Api\V1\Messaging;

use App\Http\Requests\Api\V1\ApiRequest;

class SendMessageRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            'client_message_id' => ['nullable', 'string', 'max:255'],
            'reply_to_message_id' => ['nullable', 'string', 'regex:/^(?:[0-9a-fA-F]{32}|[0-9a-fA-F-]{36})$/'],
        ];
    }
}
