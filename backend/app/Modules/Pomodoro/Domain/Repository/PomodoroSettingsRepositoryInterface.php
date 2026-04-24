<?php

namespace App\Modules\Pomodoro\Domain\Repository;

use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSettings;

interface PomodoroSettingsRepositoryInterface
{
    public function create(int $userId, int $workDuration): PomodoroSettings;

    public function update(int $userId, string $column, int $value): bool;

    public function getByUserId(int $userId): ?PomodoroSettings;
}
