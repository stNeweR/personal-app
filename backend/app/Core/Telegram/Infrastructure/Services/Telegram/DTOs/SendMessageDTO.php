<?php

namespace App\Core\Telegram\Infrastructure\Services\Telegram\DTOs;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class SendMessageDTO extends Data
{
    public function __construct(
        public readonly int $chatId,
        public readonly string $text,
        public readonly string $parseMode = 'HTML'
    ) {}
}
