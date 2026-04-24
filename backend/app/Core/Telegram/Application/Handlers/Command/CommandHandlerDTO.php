<?php

namespace App\Core\Telegram\Application\Handlers\Command;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class CommandHandlerDTO extends Data
{
    public function __construct(
        public int $updateId,
        public int $telegramId,
        public ?string $message
    ) {}
}
