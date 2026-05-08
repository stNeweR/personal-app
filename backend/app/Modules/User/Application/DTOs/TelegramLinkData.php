<?php

namespace App\Modules\User\Application\DTOs;

final readonly class TelegramLinkData
{
    public function __construct(
        public string $token,
        public string $botName
    ) {}
}
