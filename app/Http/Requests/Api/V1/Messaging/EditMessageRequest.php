<?php

namespace App\Http\Requests\Api\V1\Messaging;

use App\Http\Requests\Api\V1\ApiRequest;

class EditMessageRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
