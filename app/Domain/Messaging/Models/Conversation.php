<?php

namespace App\Domain\Messaging\Models;

use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $table = 'conversations';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_low_id',
        'user_high_id',
        'low_last_delivered_sequence',
        'low_last_read_sequence',
        'high_last_delivered_sequence',
        'high_last_read_sequence',
    ];

    protected function casts(): array
    {
        return [
            'low_last_delivered_sequence' => 'integer',
            'low_last_read_sequence' => 'integer',
            'high_last_delivered_sequence' => 'integer',
            'high_last_read_sequence' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public function lowUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_low_id');
    }

    public function highUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_high_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function containsUser(string $userId): bool
    {
        return $this->user_low_id === $userId || $this->user_high_id === $userId;
    }

    public function otherUserId(string $userId): string
    {
        return $this->user_low_id === $userId ? $this->user_high_id : $this->user_low_id;
    }
}
