<?php

namespace App\Modules\User\Domain\Repository;

use App\Modules\User\Infrastructure\Models\EmailVerificationToken;

interface EmailVerificationTokenRepositoryInterface
{
    public function create(int $userId, string $email, string $token, \DateTimeInterface $expiresAt): EmailVerificationToken;

    public function findValidByToken(string $token, string $email): ?EmailVerificationToken;

    public function invalidateForUser(int $userId): void;
}
