<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\DTOs;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class PomodoroSettingsResponseDTO extends Data
{
    public function __construct(
        public readonly int $workDuration,
        public readonly int $breakDuration,
        public readonly int $repeatsCount,
        public readonly ?int $longBreakDuration,
        public readonly ?int $cyclesBeforeLongBreak,
    ) {}
}
