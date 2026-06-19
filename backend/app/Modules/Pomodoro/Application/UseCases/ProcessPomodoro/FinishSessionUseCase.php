<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\UseCases\ProcessPomodoro;

use App\Modules\Pomodoro\Application\Events\PomodoroPhaseChangedEvent;
use App\Modules\Pomodoro\Domain\Repository\PomodoroSessionsRepositoryInterface;
use Illuminate\Support\Facades\Log;

final readonly class FinishSessionUseCase
{
    public function __construct(
        private PomodoroSessionsRepositoryInterface $pomodoroSessionsRepository,
    ) {}

    public function handle(int $sessionId, int $userId): void
    {
        $oldStatus = $this->pomodoroSessionsRepository->getBySessionId($sessionId)->current_status;

        $this->pomodoroSessionsRepository->endSession($sessionId);

        Log::info('finish');

        event(new PomodoroPhaseChangedEvent(
            userId: $userId,
            oldStatus: $oldStatus->value,
            newStatus: 'finished',
        ));
    }
}
