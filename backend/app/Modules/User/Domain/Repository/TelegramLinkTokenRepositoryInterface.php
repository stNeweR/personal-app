<?php

namespace App\Modules\User\Domain\Repository;

use App\Modules\User\Infrastructure\Models\TelegramLinkToken;

interface TelegramLinkTokenRepositoryInterface
{
    public function create(int $userId, string $token, \DateTimeInterface $expiresAt): TelegramLinkToken;

    public function findByToken(string $token): ?TelegramLinkToken;

    public function markAsUsed(TelegramLinkToken $token): void;
}
