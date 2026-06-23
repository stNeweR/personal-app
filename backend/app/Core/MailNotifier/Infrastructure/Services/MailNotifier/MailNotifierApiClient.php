<?php

declare(strict_types=1);

namespace App\Core\MailNotifier\Infrastructure\Services\MailNotifier;

use App\Core\MailNotifier\Domain\Contracts\MailNotifierApiClientInterface;
use App\Core\MailNotifier\Infrastructure\Services\MailNotifier\DTOs\MailNotifierResponse;
use App\Core\MailNotifier\Infrastructure\Services\MailNotifier\DTOs\SendEmailDTO;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class MailNotifierApiClient implements MailNotifierApiClientInterface
{
    public function sendEmail(SendEmailDTO $dto): MailNotifierResponse
    {
        try {
            Mail::raw($dto->body, function ($message) use ($dto) {
                $message->to($dto->to);
                $message->subject($dto->subject);

                if ($dto->isHtml) {
                    $message->html($dto->body);
                }
            });

            Log::info('Email sent successfully', [
                'to' => $dto->to,
                'subject' => $dto->subject,
            ]);

            return new MailNotifierResponse(
                ok: true,
                description: 'Email sent successfully',
            );
        } catch (\Exception $e) {
            Log::error('Failed to send email', [
                'to' => $dto->to,
                'subject' => $dto->subject,
                'error' => $e->getMessage(),
            ]);

            return new MailNotifierResponse(
                ok: false,
                description: $e->getMessage(),
            );
        }
    }
}