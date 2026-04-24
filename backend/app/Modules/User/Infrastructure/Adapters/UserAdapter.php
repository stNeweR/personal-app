<?php

namespace App\Modules\User\Infrastructure\Adapters;

use App\Modules\Pomodoro\Domain\Enums\StateValue;
use App\Modules\User\Domain\Contracts\UserAdapterInterface;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Models\UserState;
use App\Modules\User\Infrastructure\Repository\UserRepository;
use App\Modules\User\Infrastructure\Repository\UserStateRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class UserAdapter implements UserAdapterInterface
{
    public function __construct(
        public UserRepository $userRepository,
        public UserStateRepository $userStateRepository
    ) {}

    public function updateUserState(int $telegramId, StateValue $stateValue): UserState
    {
        $this->userStateRepository->clearUserStatesByTelegramId($telegramId);

        return $this->userStateRepository->createByTelegramId($telegramId, $stateValue);
    }

    /**
     * @throws ModelNotFoundException
     */
    public function getUserByTelegramId(int $telegramId): User
    {
        return $this->userRepository->getByTelegramId($telegramId);
    }

    public function clearUserState(int $telegramId): int
    {
        return $this->userStateRepository->clearUserStatesByTelegramId($telegramId);
    }
}
