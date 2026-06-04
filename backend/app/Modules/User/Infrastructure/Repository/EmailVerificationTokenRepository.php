<?php

namespace App\Modules\User\Infrastructure\Repository;

use App\Modules\User\Domain\Repository\EmailVerificationTokenRepositoryInterface;
use App\Modules\User\Infrastructure\Models\EmailVerificationToken;

final class EmailVerificationTokenRepository implements EmailVerificationTokenRepositoryInterface
{
    public function create(int $userId, string $email, string $token, \DateTimeInterface $expiresAt): EmailVerificationToken
    {
        return EmailVerificationToken::query()->create([
            'user_id' => $userId,
            'email' => $email,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);
    }

    public function findValidByToken(string $token, string $email): ?EmailVerificationToken
    {
        return EmailVerificationToken::query()
            ->where('token', $token)
            ->where('email', $email)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function invalidateForUser(int $userId): void
    {
        EmailVerificationToken::query()
            ->where('user_id', $userId)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);
    }
}
