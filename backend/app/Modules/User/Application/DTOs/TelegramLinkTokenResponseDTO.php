<?php

declare(strict_types=1);

namespace App\Modules\User\Application\DTOs;

use Spatie\LaravelData\Data;

final class TelegramLinkTokenResponseDTO extends Data
{
    public function __construct(
        public readonly string $link_url,
    ) {}
}
