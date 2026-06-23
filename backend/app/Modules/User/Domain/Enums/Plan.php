<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Enums;

enum Plan: string
{
    case Junior = 'junior';
    case Middle = 'middle';
    case Senior = 'senior';

    public function pluginLimit(): ?int
    {
        return match ($this) {
            self::Junior => 0,
            self::Middle => 2,
            self::Senior => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Junior => 'Junior',
            self::Middle => 'Middle',
            self::Senior => 'Senior',
        };
    }
}
