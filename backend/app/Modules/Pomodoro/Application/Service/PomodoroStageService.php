<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\Service;

use App\Core\Telegram\Domain\Contracts\TelegramAdapterInterface;
use App\Modules\Pomodoro\Application\UseCases\ProcessPomodoro\BreakSessionUseCase;
use App\Modules\Pomodoro\Application\UseCases\ProcessPomodoro\FinishSessionUseCase;
use App\Modules\Pomodoro\Application\UseCases\ProcessPomodoro\StartSessionUseCase;
use App\Modules\Pomodoro\Domain\Enums\PomodoroStatusValue;
use App\Modules\Pomodoro\Domain\Repository\PomodoroSessionsRepositoryInterface;
use App\Modules\Pomodoro\Domain\Repository\PomodoroSettingsRepositoryInterface;
use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use Illuminate\Support\Facades\Log;

final readonly class PomodoroStageService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PomodoroSessionsRepositoryInterface $pomodoroSessionsRepository,
        private PomodoroSettingsRepositoryInterface $pomodoroSettingsRepository,
        private StartSessionUseCase $startWorkUseCase,
        private TelegramAdapterInterface $telegramAdapter,
        private FinishSessionUseCase $finishSessionUseCase,
        private BreakSessionUseCase $breakSessionUseCase,
    ) {}

    public function resolve(int $sessionId, int $currentCycle, PomodoroStatusValue $currentStatus): void
    {
        $session = $this->pomodoroSessionsRepository->getBySessionId($sessionId);
        $settings = $this->pomodoroSettingsRepository->getByUserId($session->user_id);
        $user = $this->userRepository->getByUserId($session->user_id);

        if (! $settings) {
            Log::info('test');
            if ($user->telegram_id !== null) {
                $this->telegramAdapter->sendMessage(
                    chatId: $user->telegram_id,
                    text: __('pomodoro.setup_pomodoro_first')
                );
            }

            return;
        }

        $totalCycles = $settings->repeats_count ?? 1;

        if ($session->current_status === PomodoroStatusValue::PAUSED ||
            $session->current_status === PomodoroStatusValue::FINISHED) {
            return;
        }

        if ($currentStatus === PomodoroStatusValue::WORK) {
            $this->startWorkUseCase->handle(
                sessionId: $sessionId,
                userId: $user->id,
                currentCycle: $currentCycle,
                workDuration: $settings->work_duration,
                totalCycles: $settings->repeats_count,
            );
        } elseif ($currentStatus === PomodoroStatusValue::FINISHED) {
            $this->finishSessionUseCase->handle($sessionId, $user->id);
        } else {
            $this->breakSessionUseCase->handle(
                sessionId: $sessionId,
                userId: $user->id,
                currentCycle: $currentCycle,
                settings: $settings,
                totalCycles: $totalCycles,
            );
        }
    }
}
