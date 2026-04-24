<?php

namespace App\Modules\User\Domain\Repository;

use App\Modules\Pomodoro\Domain\Enums\StateValue;
use App\Modules\User\Infrastructure\Models\UserState;

interface UserStateRepositoryInterface
{
    public function clearUserStatesByTelegramId(int $telegramId): int;

    public function createByTelegramId(int $telegramId, StateValue $stateValue): UserState;

    public function getUserStateByTelegramId(int $telegramId): ?UserState;
}
