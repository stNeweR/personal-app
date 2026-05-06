<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\DTOs;

use Spatie\LaravelData\Data;

final class PomodoroSessionDTO extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $current_status,
        public readonly ?string $start_at,
        public readonly ?string $end_at,
        public readonly int $current_cycle,
    ) {}
}
