<?php

namespace App\Modules\User;

use App\Modules\User\Domain\Contracts\UserAdapterInterface;
use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use App\Modules\User\Domain\Repository\UserStateRepositoryInterface;
use App\Modules\User\Infrastructure\Adapters\UserAdapter;
use App\Modules\User\Infrastructure\Repository\UserRepository;
use App\Modules\User\Infrastructure\Repository\UserStateRepository;
use Illuminate\Support\ServiceProvider;

final class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserAdapterInterface::class, UserAdapter::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(UserStateRepositoryInterface::class, UserStateRepository::class);
    }
}
