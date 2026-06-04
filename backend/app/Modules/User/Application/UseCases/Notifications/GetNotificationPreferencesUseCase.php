<?php

declare(strict_types=1);

namespace App\Modules\User\Application\UseCases\Notifications;

use App\Modules\User\Application\DTOs\EmailChannelInfoDTO;
use App\Modules\User\Application\DTOs\NotificationPreferencesResponseDTO;
use App\Modules\User\Application\DTOs\TelegramChannelInfoDTO;
use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

final readonly class GetNotificationPreferencesUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function execute(): NotificationPreferencesResponseDTO
    {
        /** @var \App\Modules\User\Infrastructure\Models\User $user */
        $user = Auth::user();
        $fullUser = $this->userRepository->getByUserId($user->id);

        return new NotificationPreferencesResponseDTO(
            channel: $fullUser->notification_channel,
            telegram: new TelegramChannelInfoDTO(
                linked: $fullUser->telegram_id !== null,
                telegramId: $fullUser->telegram_id,
                botName: Config::string('telegram.telegram_bot_name', '') ?: null,
            ),
            email: new EmailChannelInfoDTO(
                address: $fullUser->email,
                verified: $fullUser->email_verified_at !== null,
            ),
        );
    }
}
