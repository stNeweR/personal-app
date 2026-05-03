<?php

namespace App\Modules\User\Domain\Repository;

use App\Modules\User\Infrastructure\Models\User;

interface UserRepositoryInterface
{
    public function createUser(int $telegramId): User;

    public function getByTelegramId(int $telegramId): User;

    public function getByUserId(int $userId): User;

    public function findByEmail(string $email): ?User;

    public function createApiUser(string $name, string $email, string $password): User;
}
