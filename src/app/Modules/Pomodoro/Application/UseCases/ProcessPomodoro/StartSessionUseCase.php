<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\UseCases\ProcessPomodoro;

use App\Core\Telegram\Domain\Contracts\TelegramAdapterInterface;
use App\Modules\Pomodoro\Application\Jobs\ProcessPomodoroStageJob;
use App\Modules\Pomodoro\Domain\Enums\PomodoroStatusValue;
use App\Modules\Pomodoro\Infrastructure\Repository\PomodoroSessionsRepository;
use Illuminate\Support\Facades\Log;

final readonly class StartSessionUseCase
{
    public function __construct(
        private PomodoroSessionsRepository $pomodoroSessionsRepository,
        private TelegramAdapterInterface $telegramAdapter,

    ) {}

    public function handle(int $sessionId, int $currentCycle, int $workDuration, int $totalCycles, int $chatId): void
    {
        Log::info('work');
        $this->pomodoroSessionsRepository->updateSessionStatus($sessionId, PomodoroStatusValue::WORK, $currentCycle);
        $this->telegramAdapter->sendMessage(
            chatId: $chatId,
            text: __('pomodoro.work_started', ['duration' => $workDuration])
        );

        $delay = now()->addMinutes($workDuration);

        if ($currentCycle >= $totalCycles) {
            $delay = now()->addMinutes($workDuration);
            ProcessPomodoroStageJob::dispatch(
                $sessionId,
                $currentCycle,
                PomodoroStatusValue::FINISHED
            )->delay($delay);
        } else {
            ProcessPomodoroStageJob::dispatch(
                $sessionId,
                $currentCycle,
                PomodoroStatusValue::BREAK
            )->delay($delay);
        }
    }
}
