<?php

namespace App\Modules\User\Infrastructure\Repository;

use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use App\Modules\User\Infrastructure\Models\User;

class UserRepository implements UserRepositoryInterface
{
    public function createUser(int $telegramId): User
    {
        return User::query()
            ->firstOrCreate([
                'telegram_id' => $telegramId,
            ]);
    }

    public function getByTelegramId(int $telegramId): User
    {
        return User::query()
            ->where('telegram_id', $telegramId)
            ->firstOrFail();
    }

    public function getByUserId(int $userId): User
    {
        return User::query()
            ->findOrFail($userId);
    }
}
