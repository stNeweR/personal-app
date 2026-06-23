<?php

declare(strict_types=1);

namespace App\Modules\User\Application\UseCases\Auth;

use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Support\Facades\Auth;

final readonly class LogoutUserUseCase
{
    public function execute(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user !== null) {
            /** @phpstan-ignore method.notFound */
            $user->currentAccessToken()->delete();
        }

        Auth::logout();
    }
}
