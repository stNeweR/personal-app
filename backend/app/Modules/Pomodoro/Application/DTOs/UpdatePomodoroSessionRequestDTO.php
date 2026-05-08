<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\DTOs;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class UpdatePomodoroSessionRequestDTO extends Data
{
    public function __construct(
        public readonly string $currentStatus,
        public readonly int $currentCycle,
        public readonly ?string $previousStatus = null,
        public readonly ?string $phaseStartedAt = null,
        public readonly ?int $timeLeft = null,
    ) {}
}
