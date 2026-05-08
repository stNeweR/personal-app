<?php

declare(strict_types=1);

namespace App\Modules\User\Application\UseCases\Auth;

use App\Modules\User\Domain\Repository\TelegramLinkTokenRepositoryInterface;
use App\Modules\User\Domain\Repository\UserRepositoryInterface;

final readonly class LinkTelegramAccountUseCase
{
    public function __construct(
        private TelegramLinkTokenRepositoryInterface $tokenRepository,
        private UserRepositoryInterface $userRepository,
    ) {}

    public function execute(string $token, int $telegramId): void
    {
        $linkToken = $this->tokenRepository->findByToken($token);

        if ($linkToken === null) {
            throw new \InvalidArgumentException('Invalid link token');
        }

        if ($linkToken->used_at !== null) {
            throw new \InvalidArgumentException('Link token already used');
        }

        if ($linkToken->expires_at->isPast()) {
            throw new \InvalidArgumentException('Link token expired');
        }

        $user = $this->userRepository->getByUserId($linkToken->user_id);
        $user->update(['telegram_id' => $telegramId]);

        $this->tokenRepository->markAsUsed($linkToken);
    }
}
