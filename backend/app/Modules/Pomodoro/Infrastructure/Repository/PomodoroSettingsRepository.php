<?php

namespace App\Modules\Pomodoro\Infrastructure\Repository;

use App\Modules\Pomodoro\Domain\Repository\PomodoroSettingsRepositoryInterface;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSettings;

final class PomodoroSettingsRepository implements PomodoroSettingsRepositoryInterface
{
    public function create(int $userId, int $workDuration): PomodoroSettings
    {
        return PomodoroSettings::query()
            ->firstOrCreate([
                'user_id' => $userId,
                'work_duration' => $workDuration,
            ]);
    }

    public function update(int $userId, string $column, int $value): bool
    {
        return PomodoroSettings::query()
            ->where('user_id', $userId)
            ->firstOrFail()
            ->update([
                $column => $value,
            ]);
    }

    public function getByUserId(int $userId): ?PomodoroSettings
    {
        return PomodoroSettings::query()
            ->firstWhere('user_id', $userId);
    }

    public function upsert(int $userId, array $data): PomodoroSettings
    {
        $settings = PomodoroSettings::query()->firstWhere('user_id', $userId);

        if ($settings) {
            $settings->update($data);

            return $settings->refresh();
        }

        return PomodoroSettings::query()->create(array_merge(['user_id' => $userId], $data));
    }
}
