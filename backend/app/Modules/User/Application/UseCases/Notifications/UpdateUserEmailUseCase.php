<?php

declare(strict_types=1);

namespace App\Modules\User\Application\UseCases\Notifications;

use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;

final readonly class UpdateUserEmailUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function execute(string $email): void
    {
        /** @var \App\Modules\User\Infrastructure\Models\User $user */
        $user = Auth::user();
        $fullUser = $this->userRepository->getByUserId($user->id);

        $fullUser->update([
            'email' => $email,
            'email_verified_at' => null,
        ]);
    }
}
