<?php

namespace App\Core\Telegram\Infrastructure\Services\Telegram\DTOs;

use Spatie\LaravelData\Data;

final class TelegramApiResponse extends Data
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?string $description,
        public readonly ?int $error_code,
    ) {}
}
