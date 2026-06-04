<?php

declare(strict_types=1);

namespace App\Modules\User\Application\UseCases\Auth;

use App\Modules\User\Application\DTOs\TelegramStatusResponseDTO;
use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

final readonly class GetTelegramStatusUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function execute(): TelegramStatusResponseDTO
    {
        /** @var \App\Modules\User\Infrastructure\Models\User $user */
        $user = Auth::user();
        $fullUser = $this->userRepository->getByUserId($user->id);

        return new TelegramStatusResponseDTO(
            linked: $fullUser->telegram_id !== null,
            telegramId: $fullUser->telegram_id,
            botName: Config::string('telegram.telegram_bot_name', '') ?: null,
        );
    }
}
