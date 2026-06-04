<?php

declare(strict_types=1);

namespace App\Modules\User\Application\DTOs;

use Spatie\LaravelData\Data;

final class TelegramChannelInfoDTO extends Data
{
    public function __construct(
        public readonly bool $linked,
        public readonly ?int $telegramId,
        public readonly ?string $botName,
    ) {}
}
