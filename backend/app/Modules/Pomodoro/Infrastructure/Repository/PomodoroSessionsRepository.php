<?php

namespace App\Modules\Pomodoro\Infrastructure\Repository;

use App\Modules\Pomodoro\Domain\Enums\PomodoroStatusValue;
use App\Modules\Pomodoro\Domain\Repository\PomodoroSessionsRepositoryInterface;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSession;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class PomodoroSessionsRepository implements PomodoroSessionsRepositoryInterface
{
    public function getBySessionId(int $sessionId): PomodoroSession
    {
        return PomodoroSession::query()
            ->findOrFail($sessionId);
    }

    /**
     * @param  array<string, mixed>|null  $settings
     */
    public function create(int $userId, ?array $settings = null): PomodoroSession
    {
        return PomodoroSession::query()->create([
            'user_id' => $userId,
            'current_status' => PomodoroStatusValue::WORK,
            'start_at' => now(),
            'current_cycle' => 1,
            'settings' => $settings,
        ]);
    }

    public function findActiveSession(int $userId): ?PomodoroSession
    {
        return PomodoroSession::query()->where('user_id', $userId)
            ->whereNotIn('current_status', [PomodoroStatusValue::FINISHED, PomodoroStatusValue::PAUSED])
            ->first();
    }

    /**
     * @return Collection<int, PomodoroSession>
     */
    public function getTodaySessions(int $userId): Collection
    {
        return PomodoroSession::query()->where('user_id', $userId)
            ->whereDate('start_at', Carbon::today())
            ->orderBy('start_at', 'asc')
            ->get();
    }

    public function refreshActiveSession(int $userId): PomodoroSession
    {
        $session = $this->findActiveSession($userId);

        if (is_null($session)) {
            throw new ModelNotFoundException;
        }

        return $session->refresh();
    }

    public function updateSessionStatus(int $sessionId, PomodoroStatusValue $status, int $currentCycle): bool
    {
        return $this->getBySessionId($sessionId)->update([
            'current_status' => $status,
            'current_cycle' => $currentCycle,
        ]);
    }

    public function endSession(int $sessionId): bool
    {
        return $this->getBySessionId($sessionId)->update([
            'current_status' => PomodoroStatusValue::FINISHED,
            'end_at' => now(),
        ]);
    }
}
