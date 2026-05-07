<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\UseCases;

use App\Modules\Pomodoro\Application\DTOs\PomodoroSessionDTO;
use App\Modules\Pomodoro\Application\DTOs\UpdatePomodoroSessionRequestDTO;
use App\Modules\Pomodoro\Domain\Enums\PomodoroStatusValue;
use App\Modules\Pomodoro\Domain\Repository\PomodoroSessionsRepositoryInterface;

final readonly class UpdatePomodoroSessionUseCase
{
    public function __construct(
        private PomodoroSessionsRepositoryInterface $pomodoroSessionsRepository,
    ) {}

    public function execute(int $sessionId, UpdatePomodoroSessionRequestDTO $data): PomodoroSessionDTO
    {
        $status = PomodoroStatusValue::from($data->currentStatus);

        $this->pomodoroSessionsRepository->updateSessionStatus(
            sessionId: $sessionId,
            status: $status,
            currentCycle: $data->currentCycle,
        );

        if ($status === PomodoroStatusValue::FINISHED) {
            $this->pomodoroSessionsRepository->endSession($sessionId);
        }

        $session = $this->pomodoroSessionsRepository->getBySessionId($sessionId);

        return new PomodoroSessionDTO(
            id: $session->id,
            current_status: $session->current_status->value,
            start_at: $session->start_at?->format('Y-m-d H:i:s'),
            end_at: $session->end_at?->format('Y-m-d H:i:s'),
            current_cycle: $session->current_cycle,
        );
    }
}
