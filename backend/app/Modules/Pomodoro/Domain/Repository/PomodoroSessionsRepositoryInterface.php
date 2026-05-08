<?php

namespace App\Modules\Pomodoro\Domain\Repository;

use App\Modules\Pomodoro\Domain\Enums\PomodoroStatusValue;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSession;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

interface PomodoroSessionsRepositoryInterface
{
    /**
     * @param  array<string, mixed>|null  $settings
     */
    public function create(int $userId, ?array $settings = null): PomodoroSession;

    public function findActiveSession(int $userId): ?PomodoroSession;

    /** @return Collection<int, PomodoroSession> */
    public function getTodaySessions(int $userId): Collection;

    public function refreshActiveSession(int $userId): PomodoroSession;

    public function getBySessionId(int $sessionId): PomodoroSession;

    public function updateSessionStatus(
        int $sessionId,
        PomodoroStatusValue $status,
        int $currentCycle,
        ?PomodoroStatusValue $previousStatus = null,
        ?Carbon $phaseStartedAt = null,
        ?int $timeLeft = null,
    ): bool;

    public function endSession(int $sessionId): bool;

    public function delete(int $sessionId): bool;
}
