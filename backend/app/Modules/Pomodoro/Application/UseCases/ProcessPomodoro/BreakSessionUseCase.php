<?php

namespace App\Modules\Pomodoro\Application\UseCases\ProcessPomodoro;

use App\Core\Telegram\Domain\Contracts\TelegramAdapterInterface;
use App\Modules\Pomodoro\Application\Jobs\ProcessPomodoroStageJob;
use App\Modules\Pomodoro\Domain\Enums\PomodoroStatusValue;
use App\Modules\Pomodoro\Domain\Repository\PomodoroSessionsRepositoryInterface;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSettings;
use Illuminate\Support\Facades\Log;

final readonly class BreakSessionUseCase
{
    public function __construct(
        private PomodoroSessionsRepositoryInterface $pomodoroSessionsRepository,
        private TelegramAdapterInterface $telegramAdapter
    ) {}

    public function handle(int $sessionId, int $chatId, int $currentCycle, PomodoroSettings $settings, int $totalCycles): void
    {
        if ($currentCycle % $settings->cycles_before_long_break === 0 && $currentCycle !== $totalCycles && $settings->long_break_duration) {
            Log::info('long_break');
            $this->pomodoroSessionsRepository->updateSessionStatus($sessionId, PomodoroStatusValue::LONG_BREAK, $currentCycle);
            $this->telegramAdapter->sendMessage(
                chatId: $chatId,
                text: __('pomodoro.long_break_started', [
                    'cycle' => $currentCycle,
                    'duration' => $settings->long_break_duration,
                ])
            );

            $delay = now()->addMinutes($settings->long_break_duration);
        } else {
            Log::info('break');
            $this->pomodoroSessionsRepository->updateSessionStatus($sessionId, PomodoroStatusValue::BREAK, $currentCycle);

            $this->telegramAdapter->sendMessage(
                chatId: $chatId,
                text: __('pomodoro.short_break_started', ['duration' => $settings->break_duration])
            );

            $delay = now()->addMinutes($settings->break_duration);
        }

        ProcessPomodoroStageJob::dispatch(
            $sessionId,
            $currentCycle,
            PomodoroStatusValue::WORK
        )->delay($delay);
    }
}
