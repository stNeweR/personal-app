<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\UseCases;

use App\Modules\Pomodoro\Application\DTOs\PomodoroSettingsResponseDTO;
use App\Modules\Pomodoro\Domain\Repository\PomodoroSettingsRepositoryInterface;
use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Support\Facades\Auth;

final readonly class GetPomodoroSettingsForWebUseCase
{
    public function __construct(
        private PomodoroSettingsRepositoryInterface $pomodoroSettingsRepository,
    ) {}

    public function execute(): ?PomodoroSettingsResponseDTO
    {
        /** @var User $user */
        $user = Auth::user();
        $settings = $this->pomodoroSettingsRepository->getByUserId($user->id);

        if (! $settings) {
            return null;
        }

        return new PomodoroSettingsResponseDTO(
            workDuration: $settings->work_duration,
            breakDuration: $settings->break_duration,
            repeatsCount: $settings->repeats_count,
            longBreakDuration: $settings->long_break_duration,
            cyclesBeforeLongBreak: $settings->cycles_before_long_break,
        );
    }
}
