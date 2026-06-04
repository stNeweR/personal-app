<?php

declare(strict_types=1);

namespace App\Core\MailNotifier\Infrastructure\Services\MailNotifier\DTOs;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class SendEmailDTO extends Data
{
    public function __construct(
        public readonly string $to,
        public readonly string $subject,
        public readonly string $body,
        public readonly bool $isHtml = false,
    ) {}
}
