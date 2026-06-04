<?php

declare(strict_types=1);

namespace App\Modules\User\Application\UseCases\Notifications;

use App\Modules\User\Domain\Repository\EmailVerificationTokenRepositoryInterface;
use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use Illuminate\Support\Carbon;

final readonly class VerifyEmailUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EmailVerificationTokenRepositoryInterface $tokenRepository,
    ) {}

    /**
     * @throws \InvalidArgumentException
     */
    public function execute(string $token, string $email): void
    {
        $record = $this->tokenRepository->findValidByToken($token, $email);

        if ($record === null) {
            throw new \InvalidArgumentException(__('notifications.verification_link_invalid'));
        }

        $user = $this->userRepository->getByUserId($record->user_id);

        $user->update([
            'email' => $email,
            'email_verified_at' => Carbon::now(),
        ]);

        $record->update(['used_at' => Carbon::now()]);
    }
}
