<?php

declare(strict_types=1);

namespace App\Modules\User\Application\UseCases\Auth;

use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;

final readonly class DisconnectTelegramUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function execute(): void
    {
        /** @var \App\Modules\User\Infrastructure\Models\User $user */
        $user = Auth::user();
        $fullUser = $this->userRepository->getByUserId($user->id);

        $fullUser->update([
            'telegram_id' => null,
            'notification_channel' => $fullUser->notification_channel === 'telegram' ? null : $fullUser->notification_channel,
        ]);
    }
}
