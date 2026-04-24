<?php

namespace App\Modules\User\Domain\Contracts;

use App\Modules\Pomodoro\Domain\Enums\StateValue;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Models\UserState;

interface UserAdapterInterface
{
    public function getUserByTelegramId(int $telegramId): User;

    public function updateUserState(int $userId, StateValue $stateValue): UserState;

    public function clearUserState(int $telegramId): int;
}
