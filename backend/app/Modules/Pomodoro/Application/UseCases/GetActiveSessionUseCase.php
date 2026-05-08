<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\UseCases;

use App\Modules\Pomodoro\Application\DTOs\PomodoroSessionDTO;
use App\Modules\Pomodoro\Domain\Repository\PomodoroSessionsRepositoryInterface;
use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Support\Facades\Auth;

final readonly class GetActiveSessionUseCase
{
    public function __construct(
        private PomodoroSessionsRepositoryInterface $pomodoroSessionsRepository,
    ) {}

    public function execute(): ?PomodoroSessionDTO
    {
        /** @var User $user */
        $user = Auth::user();
        $session = $this->pomodoroSessionsRepository->findActiveSession($user->id);

        if ($session === null) {
            return null;
        }

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
