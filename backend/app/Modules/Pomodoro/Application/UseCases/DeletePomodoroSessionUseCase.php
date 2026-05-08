<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\UseCases;

use App\Modules\Pomodoro\Domain\Repository\PomodoroSessionsRepositoryInterface;

final readonly class DeletePomodoroSessionUseCase
{
    public function __construct(
        private PomodoroSessionsRepositoryInterface $pomodoroSessionsRepository,
    ) {}

    public function execute(int $sessionId): bool
    {
        return $this->pomodoroSessionsRepository->delete($sessionId);
    }
}
