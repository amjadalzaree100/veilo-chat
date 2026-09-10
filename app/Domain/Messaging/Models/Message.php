<?php

namespace App\Domain\Messaging\Models;

use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Infrastructure\Messaging\MessageCipher;

class Message extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'messages';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'conversation_id',
        'sender_id',
        'sequence_no',
        'client_message_id',
        'reply_to_message_id',
        'body_ciphertext',
        'encryption_key_version',
        'edited_at',
        'deleted_at',
    ];

    protected $hidden = [
        'body_ciphertext',
    ];

    protected function casts(): array
    {
        return [
            'sequence_no' => 'integer',
            'encryption_key_version' => 'integer',
            'edited_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_message_id');
    }

    public function plaintextBody(): string
    {
        return app(MessageCipher::class)->decrypt($this->body_ciphertext, $this->encryption_key_version);
    }
}
