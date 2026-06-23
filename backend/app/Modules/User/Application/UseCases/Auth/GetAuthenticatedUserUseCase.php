<?php

declare(strict_types=1);

namespace App\Modules\User\Application\UseCases\Auth;

use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Support\Facades\Auth;

final readonly class GetAuthenticatedUserUseCase
{
    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        /** @var User $user */
        $user = Auth::user();

        return [
            'id' => $user->id,
            'plan' => $user->plan->value,
            'name' => $user->name,
            'email' => $user->email,
            'telegram_id' => $user->telegram_id,
        ];
    }
}
