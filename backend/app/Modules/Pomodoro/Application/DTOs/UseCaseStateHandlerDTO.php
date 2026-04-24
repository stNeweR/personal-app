<?php

namespace App\Modules\Pomodoro\Application\DTOs;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class UseCaseStateHandlerDTO extends Data
{
    public function __construct(
        public readonly int $telegramId,
        public readonly string $message
    ) {}
}
