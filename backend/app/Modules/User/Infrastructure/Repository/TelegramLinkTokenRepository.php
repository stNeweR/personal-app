<?php

namespace App\Modules\User\Infrastructure\Repository;

use App\Modules\User\Domain\Repository\TelegramLinkTokenRepositoryInterface;
use App\Modules\User\Infrastructure\Models\TelegramLinkToken;

final class TelegramLinkTokenRepository implements TelegramLinkTokenRepositoryInterface
{
    public function create(int $userId, string $token, \DateTimeInterface $expiresAt): TelegramLinkToken
    {
        return TelegramLinkToken::query()->create([
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);
    }

    public function findByToken(string $token): ?TelegramLinkToken
    {
        return TelegramLinkToken::query()
            ->where('token', $token)
            ->first();
    }

    public function markAsUsed(TelegramLinkToken $token): void
    {
        $token->update(['used_at' => now()]);
    }
}
