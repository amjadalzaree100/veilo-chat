<?php

namespace App\Http\Resources\Api\V1\Messaging;

use App\Domain\Identity\Support\PublicIdNormalizer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Throwable;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $ids = app(PublicIdNormalizer::class);
        $body = null;

        if ($this->resource->deleted_at === null) {
            try {
                $body = $this->resource->plaintextBody();
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return [
            'id' => $ids->external($this->resource->getKey()),
            'conversation_id' => $ids->external($this->resource->conversation_id),
            'sender_id' => $ids->external($this->resource->sender_id),
            'sequence_no' => $this->resource->sequence_no,
            'client_message_id' => $this->resource->client_message_id,
            'reply_to_message_id' => $ids->external($this->resource->reply_to_message_id),
            'body' => $body,
            'edited_at' => $this->resource->edited_at?->toISOString(),
            'deleted_at' => $this->resource->deleted_at?->toISOString(),
            'created_at' => $this->resource->created_at?->toISOString(),
        ];
    }
}
