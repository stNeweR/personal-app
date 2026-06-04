<?php

declare(strict_types=1);

namespace App\Modules\User\Application\DTOs;

use Spatie\LaravelData\Data;

final class TelegramRegisterTokenResponseDTO extends Data
{
    public function __construct(
        public readonly string $token,
        public readonly string $botName,
        public readonly int $expiresInMinutes,
    ) {}
}
