<?php

declare(strict_types=1);

namespace App\Modules\User\Application\UseCases\Auth;

use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Support\Facades\Auth;

final readonly class LogoutUserUseCase
{
    public function execute(): void
    {
        Auth::logout();
    }
}
