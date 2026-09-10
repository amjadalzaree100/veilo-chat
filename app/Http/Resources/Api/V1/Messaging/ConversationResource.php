<?php

namespace App\Http\Resources\Api\V1\Messaging;

use App\Domain\Identity\Support\PublicIdNormalizer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $ids = app(PublicIdNormalizer::class);

        return [
            'id' => $ids->external($this->resource->getKey()),
            'user_low_id' => $ids->external($this->resource->user_low_id),
            'user_high_id' => $ids->external($this->resource->user_high_id),
            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),
        ];
    }
}
