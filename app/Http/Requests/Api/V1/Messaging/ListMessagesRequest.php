<?php

namespace App\Http\Requests\Api\V1\Messaging;

use App\Http\Requests\Api\V1\ApiRequest;

class ListMessagesRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cursor' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
