<?php

namespace App\Modules\Pomodoro;

use App\Modules\Pomodoro\Domain\Repository\PomodoroSessionsRepositoryInterface;
use App\Modules\Pomodoro\Domain\Repository\PomodoroSettingsRepositoryInterface;
use App\Modules\Pomodoro\Infrastructure\Repository\PomodoroSessionsRepository;
use App\Modules\Pomodoro\Infrastructure\Repository\PomodoroSettingsRepository;
use Illuminate\Support\ServiceProvider;

final class PomodoroServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PomodoroSessionsRepositoryInterface::class, PomodoroSessionsRepository::class);
        $this->app->bind(PomodoroSettingsRepositoryInterface::class, PomodoroSettingsRepository::class);
    }
}
