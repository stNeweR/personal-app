<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\UseCases;

use App\Modules\Pomodoro\Application\DTOs\PomodoroSettingsResponseDTO;
use App\Modules\Pomodoro\Application\DTOs\SavePomodoroSettingsDTO;
use App\Modules\Pomodoro\Domain\Repository\PomodoroSettingsRepositoryInterface;
use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Support\Facades\Auth;

final readonly class SavePomodoroSettingsUseCase
{
    public function __construct(
        private PomodoroSettingsRepositoryInterface $pomodoroSettingsRepository,
    ) {}

    public function execute(SavePomodoroSettingsDTO $data): PomodoroSettingsResponseDTO
    {
        /** @var User $user */
        $user = Auth::user();

        $settings = $this->pomodoroSettingsRepository->upsert($user->id, [
            'work_duration' => $data->workDuration,
            'break_duration' => $data->breakDuration,
            'repeats_count' => $data->repeatsCount,
            'long_break_duration' => $data->longBreakDuration,
            'cycles_before_long_break' => $data->cyclesBeforeLongBreak,
        ]);

        return new PomodoroSettingsResponseDTO(
            workDuration: $settings->work_duration,
            breakDuration: $settings->break_duration,
            repeatsCount: $settings->repeats_count,
            longBreakDuration: $settings->long_break_duration,
            cyclesBeforeLongBreak: $settings->cycles_before_long_break,
        );
    }
}
