<?php

declare(strict_types=1);

namespace App\Core\MailNotifier\Infrastructure\Services\MailNotifier\DTOs;

use Spatie\LaravelData\Data;

final class MailNotifierResponse extends Data
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?string $description,
        public readonly ?string $messageId = null,
    ) {}
}
