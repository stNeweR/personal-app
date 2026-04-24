<?php

namespace App\Modules\Pomodoro\Application\DTOs;

use Spatie\LaravelData\Data;

final class GetTodaySessionsDTO extends Data
{
    public function __construct(
        public readonly int $telegramId
    ) {}
}
