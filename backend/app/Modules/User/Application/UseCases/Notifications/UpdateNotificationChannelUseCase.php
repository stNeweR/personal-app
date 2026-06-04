<?php

declare(strict_types=1);

namespace App\Modules\User\Application\UseCases\Notifications;

use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;

final readonly class UpdateNotificationChannelUseCase
{
    public const string CHANNEL_TELEGRAM = 'telegram';

    public const string CHANNEL_EMAIL = 'email';

    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    /**
     * @throws \InvalidArgumentException
     */
    public function execute(?string $channel): void
    {
        if ($channel !== null && $channel !== self::CHANNEL_TELEGRAM && $channel !== self::CHANNEL_EMAIL) {
            throw new \InvalidArgumentException('Invalid channel');
        }

        /** @var \App\Modules\User\Infrastructure\Models\User $user */
        $user = Auth::user();
        $fullUser = $this->userRepository->getByUserId($user->id);

        if ($channel === self::CHANNEL_TELEGRAM && $fullUser->telegram_id === null) {
            throw new \InvalidArgumentException(__('notifications.telegram_not_linked'));
        }

        if ($channel === self::CHANNEL_EMAIL) {
            if ($fullUser->email === null || $fullUser->email === '') {
                throw new \InvalidArgumentException(__('notifications.email_not_set'));
            }
            if ($fullUser->email_verified_at === null) {
                throw new \InvalidArgumentException(__('notifications.email_not_verified'));
            }
        }

        $fullUser->update(['notification_channel' => $channel]);
    }
}
