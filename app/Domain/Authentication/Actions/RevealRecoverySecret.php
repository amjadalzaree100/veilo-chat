<?php

namespace App\Domain\Authentication\Actions;

use App\Domain\Identity\Models\User;
use RuntimeException;

class RevealRecoverySecret
{
    public function handle(User $user): string
    {
        if ($user->recovery_secret_hidden_at !== null) {
            throw new RuntimeException('The recovery secret is hidden.');
        }

        return (string) $user->recovery_secret_encrypted;
    }
}
