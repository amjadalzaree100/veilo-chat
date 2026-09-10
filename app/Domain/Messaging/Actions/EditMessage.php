<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Exceptions\MessagingException;
use App\Domain\Messaging\Models\Message;
use App\Infrastructure\Messaging\MessageCipher;
use Illuminate\Support\Facades\DB;

class EditMessage
{
    public function __construct(private readonly MessageCipher $cipher)
    {
    }

    public function handle(User $user, Message $message, string $body): Message
    {
        return DB::transaction(function () use ($user, $message, $body): Message {
            $message = Message::query()->whereKey($message->getKey())->lockForUpdate()->firstOrFail();

            if ($message->sender_id !== $user->getKey()) {
                throw new MessagingException('Only the sender can edit this message.', 403);
            }

            if ($message->deleted_at !== null) {
                throw new MessagingException('Deleted messages cannot be edited.', 409);
            }

            $message->forceFill([
                'body_ciphertext' => $this->cipher->encrypt($body),
                'edited_at' => now(),
            ])->save();

            return $message;
        });
    }
}
