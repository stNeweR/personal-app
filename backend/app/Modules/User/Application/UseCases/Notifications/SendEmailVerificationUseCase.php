<?php

declare(strict_types=1);

namespace App\Modules\User\Application\UseCases\Notifications;

use App\Core\MailNotifier\Domain\Contracts\MailNotifierApiClientInterface;
use App\Core\MailNotifier\Infrastructure\Services\MailNotifier\DTOs\SendEmailDTO;
use App\Modules\User\Domain\Repository\EmailVerificationTokenRepositoryInterface;
use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

final readonly class SendEmailVerificationUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EmailVerificationTokenRepositoryInterface $tokenRepository,
        private MailNotifierApiClientInterface $mailClient,
    ) {}

    /**
     * @throws \InvalidArgumentException
     */
    public function execute(): void
    {
        /** @var \App\Modules\User\Infrastructure\Models\User $user */
        $user = Auth::user();
        $fullUser = $this->userRepository->getByUserId($user->id);

        if ($fullUser->email === null || $fullUser->email === '') {
            throw new \InvalidArgumentException(__('notifications.email_not_set'));
        }

        $this->tokenRepository->invalidateForUser($fullUser->id);

        $token = bin2hex(random_bytes(32));
        $expiresAt = now()->addMinutes(30);

        $this->tokenRepository->create(
            userId: $fullUser->id,
            email: $fullUser->email,
            token: $token,
            expiresAt: $expiresAt,
        );

        $verifyUrl = URL::temporarySignedRoute(
            'api.v1.notifications.email.verify',
            $expiresAt,
            ['token' => $token, 'email' => $fullUser->email],
        );

        $body = (string) __('notifications.verification_email_body', [
            'url' => $verifyUrl,
            'minutes' => 30,
        ]);

        $this->mailClient->sendEmail(new SendEmailDTO(
            to: $fullUser->email,
            subject: (string) __('notifications.verification_email_subject'),
            body: $body,
            isHtml: false,
        ));
    }
}
