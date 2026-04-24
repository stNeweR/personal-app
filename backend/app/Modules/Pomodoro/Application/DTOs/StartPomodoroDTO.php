<?php

namespace App\Modules\Pomodoro\Application\DTOs;

use Spatie\LaravelData\Data;

final class StartPomodoroDTO extends Data
{
    public function __construct(
        public readonly int $telegramId
    ) {}

}
