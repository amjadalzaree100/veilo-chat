<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\User;

class SetPrivacy
{
    public function handle(User $user, string $privacy): void
    {
        $user->forceFill(['privacy' => $privacy])->save();
    }
}
