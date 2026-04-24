<?php

namespace App\Core\Telegram\Domain\Exceptions;

use Throwable;

class UnknownCommandException extends \Exception
{
    public function __construct(string $command, int $userId, ?Throwable $previous = null)
    {
        $errorMessage = 'User with user_id = '.$userId.'write unknown command: '.$command;
        parent::__construct($errorMessage, 400, $previous);
    }
}
