<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\Events;

final readonly class PomodoroPhaseChangedEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly string $oldStatus,
        public readonly string $newStatus,
    ) {}
}
