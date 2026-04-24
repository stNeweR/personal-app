<?php

namespace App\Modules\User\Infrastructure\Repository;

use App\Modules\Pomodoro\Domain\Enums\StateValue;
use App\Modules\User\Domain\Repository\UserStateRepositoryInterface;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Models\UserState;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class UserStateRepository implements UserStateRepositoryInterface
{
    public function clearUserStatesByTelegramId(int $telegramId): int
    {
        return DB::table('user_states')
            ->join('users', 'user_states.user_id', '=', 'users.id')
            ->where('users.telegram_id', $telegramId)
            ->delete();
    }

    /**
     * @throws ModelNotFoundException
     */
    public function createByTelegramId(int $telegramId, StateValue $stateValue): UserState
    {
        $user = User::query()->where('telegram_id', $telegramId)->firstOrFail();

        return UserState::query()->create([
            'user_id' => $user->id,
            'state_value' => $stateValue->value,
        ]);
    }

    public function getUserStateByTelegramId(int $telegramId): ?UserState
    {
        $user = User::query()->where('telegram_id', $telegramId)->first();

        if (! $user) {
            return null;
        }

        return UserState::where('user_id', $user->id)->first();
    }
}
