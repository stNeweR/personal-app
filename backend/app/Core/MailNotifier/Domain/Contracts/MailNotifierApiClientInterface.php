<?php

declare(strict_types=1);

namespace App\Core\MailNotifier\Domain\Contracts;

use App\Core\MailNotifier\Infrastructure\Services\MailNotifier\DTOs\MailNotifierResponse;
use App\Core\MailNotifier\Infrastructure\Services\MailNotifier\DTOs\SendEmailDTO;

interface MailNotifierApiClientInterface
{
    public function sendEmail(SendEmailDTO $dto): MailNotifierResponse;
}
