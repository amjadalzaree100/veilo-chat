<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Exceptions\MessagingException;
use App\Domain\Messaging\Models\Message;
use Illuminate\Support\Facades\DB;

class DeleteMessage
{
    public function handle(User $user, Message $message): Message
    {
        return DB::transaction(function () use ($user, $message): Message {
            $message = Message::query()->whereKey($message->getKey())->lockForUpdate()->firstOrFail();

            if ($message->sender_id !== $user->getKey()) {
                throw new MessagingException('Only the sender can delete this message.', 403);
            }

            if ($message->deleted_at === null) {
                $message->forceFill(['deleted_at' => now()])->save();
            }

            return $message;
        });
    }
}
