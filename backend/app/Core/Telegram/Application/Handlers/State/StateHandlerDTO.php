<?php

namespace App\Core\Telegram\Application\Handlers\State;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class StateHandlerDTO extends Data
{
    public function __construct(
        public readonly int $updateId,
        public readonly ?int $telegramId,
        public readonly ?string $message
    ) {}
}
