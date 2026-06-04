<?php

declare(strict_types=1);

namespace App\Modules\User\Application\UseCases\Auth;

use App\Modules\User\Application\DTOs\TelegramLinkData;
use App\Modules\User\Domain\Repository\TelegramLinkTokenRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

final readonly class GenerateTelegramRegisterTokenUseCase
{
    public function __construct(
        private TelegramLinkTokenRepositoryInterface $tokenRepository,
    ) {}

    public function execute(): TelegramLinkData
    {
        /** @var \App\Modules\User\Infrastructure\Models\User $user */
        $user = Auth::user();

        $token = Str::random(32);

        $this->tokenRepository->create(
            userId: $user->id,
            token: $token,
            expiresAt: now()->addMinutes(15),
        );

        return new TelegramLinkData(
            token: $token,
            botName: Config::string('telegram.telegram_bot_name', ''),
        );
    }
}
