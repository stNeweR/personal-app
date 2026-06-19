<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\UseCases\ProcessPomodoro;

use App\Modules\Pomodoro\Application\Events\PomodoroPhaseChangedEvent;
use App\Modules\Pomodoro\Application\Jobs\ProcessPomodoroStageJob;
use App\Modules\Pomodoro\Domain\Enums\PomodoroStatusValue;
use App\Modules\Pomodoro\Domain\Repository\PomodoroSessionsRepositoryInterface;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSettings;
use Illuminate\Support\Facades\Log;

final readonly class BreakSessionUseCase
{
    public function __construct(
        private PomodoroSessionsRepositoryInterface $pomodoroSessionsRepository,
    ) {}

    public function handle(int $sessionId, int $userId, int $currentCycle, PomodoroSettings $settings, int $totalCycles): void
    {
        $oldStatus = $this->pomodoroSessionsRepository->getBySessionId($sessionId)->current_status;

        if ($currentCycle % $settings->cycles_before_long_break === 0 && $currentCycle !== $totalCycles && $settings->long_break_duration) {
            Log::info('long_break');
            $newStatus = PomodoroStatusValue::LONG_BREAK;
            $delay = now()->addMinutes($settings->long_break_duration);
        } else {
            Log::info('break');
            $newStatus = PomodoroStatusValue::BREAK;
            $delay = now()->addMinutes($settings->break_duration);
        }

        $this->pomodoroSessionsRepository->updateSessionStatus($sessionId, $newStatus, $currentCycle);

        event(new PomodoroPhaseChangedEvent(
            userId: $userId,
            oldStatus: $oldStatus->value,
            newStatus: $newStatus->value,
        ));

        ProcessPomodoroStageJob::dispatch(
            $sessionId,
            $currentCycle,
            PomodoroStatusValue::WORK
        )->delay($delay);
    }
}
