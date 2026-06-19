<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\UseCases\ProcessPomodoro;

use App\Modules\Pomodoro\Application\Events\PomodoroPhaseChangedEvent;
use App\Modules\Pomodoro\Application\Jobs\ProcessPomodoroStageJob;
use App\Modules\Pomodoro\Domain\Enums\PomodoroStatusValue;
use App\Modules\Pomodoro\Domain\Repository\PomodoroSessionsRepositoryInterface;
use Illuminate\Support\Facades\Log;

final readonly class StartSessionUseCase
{
    public function __construct(
        private PomodoroSessionsRepositoryInterface $pomodoroSessionsRepository,
    ) {}

    public function handle(int $sessionId, int $userId, int $currentCycle, int $workDuration, int $totalCycles): void
    {
        Log::info('work');

        $oldStatus = $this->pomodoroSessionsRepository->getBySessionId($sessionId)->current_status;

        $this->pomodoroSessionsRepository->updateSessionStatus($sessionId, PomodoroStatusValue::WORK, $currentCycle);

        event(new PomodoroPhaseChangedEvent(
            userId: $userId,
            oldStatus: $oldStatus->value,
            newStatus: PomodoroStatusValue::WORK->value,
        ));

        $delay = now()->addMinutes($workDuration);

        if ($currentCycle >= $totalCycles) {
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
