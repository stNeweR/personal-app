<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\UseCases;

use App\Modules\Pomodoro\Application\DTOs\PomodoroSessionDTO;
use App\Modules\Pomodoro\Application\DTOs\UpdatePomodoroSessionRequestDTO;
use App\Modules\Pomodoro\Domain\Enums\PomodoroStatusValue;
use App\Modules\Pomodoro\Domain\Repository\PomodoroSessionsRepositoryInterface;
use Carbon\Carbon;

final readonly class UpdatePomodoroSessionUseCase
{
    public function __construct(
        private PomodoroSessionsRepositoryInterface $pomodoroSessionsRepository,
        private NotifyPomodoroPhaseChangeUseCase $notifyPhaseChange,
    ) {}

    public function execute(int $sessionId, UpdatePomodoroSessionRequestDTO $data): PomodoroSessionDTO
    {
        $status = PomodoroStatusValue::from($data->currentStatus);
        $previousStatus = $data->previousStatus !== null
            ? PomodoroStatusValue::from($data->previousStatus)
            : null;
        $phaseStartedAt = $data->phaseStartedAt !== null
            ? Carbon::parse($data->phaseStartedAt)
            : null;

        $oldStatus = $this->pomodoroSessionsRepository->getBySessionId($sessionId)->current_status;

        $this->pomodoroSessionsRepository->updateSessionStatus(
            sessionId: $sessionId,
            status: $status,
            currentCycle: $data->currentCycle,
            previousStatus: $previousStatus,
            phaseStartedAt: $phaseStartedAt,
            timeLeft: $data->timeLeft,
        );

        if ($status === PomodoroStatusValue::FINISHED) {
            $this->pomodoroSessionsRepository->endSession($sessionId);
        }

        $session = $this->pomodoroSessionsRepository->getBySessionId($sessionId);

        $this->notifyPhaseChange->execute($session->user_id, $oldStatus, $session->current_status);

        return new PomodoroSessionDTO(
            id: $session->id,
            current_status: $session->current_status->value,
            previous_status: $session->previous_status?->value,
            start_at: $session->start_at?->format('Y-m-d H:i:s'),
            end_at: $session->end_at?->format('Y-m-d H:i:s'),
            current_cycle: $session->current_cycle,
            phase_started_at: $session->phase_started_at?->format('c'),
            time_left: $session->time_left,
        );
    }
}
