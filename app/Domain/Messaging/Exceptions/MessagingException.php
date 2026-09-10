<?php

namespace App\Domain\Messaging\Exceptions;

use RuntimeException;

class MessagingException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 422)
    {
        parent::__construct($message);
    }
}
