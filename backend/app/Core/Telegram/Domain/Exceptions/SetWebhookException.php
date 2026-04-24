<?php

namespace App\Core\Telegram\Domain\Exceptions;

use Exception;
use Throwable;

final class SetWebhookException extends Exception
{
    public function __construct(?string $message, ?int $code = null, ?Throwable $previous = null)
    {
        $code = $code ?? 400;
        $errorMessage = 'Webhook was not set - '.$message.'. With code '.$code;
        parent::__construct($errorMessage, $code, $previous);
    }
}
