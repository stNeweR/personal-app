<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\DTOs;

use Spatie\LaravelData\Data;

final class TodaySessionsResponseDTO extends Data
{
    /**
     * @param array<int, PomodoroSessionDTO> $sessions
     */
    public function __construct(
        public readonly array $sessions,
    ) {}
}
