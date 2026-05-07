<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\DTOs;

use Spatie\LaravelData\Data;

final class CreatePomodoroSessionRequestDTO extends Data
{
    /**
     * @param  array<string, mixed>|null  $settings
     */
    public function __construct(
        public readonly ?array $settings = null,
    ) {}
}
