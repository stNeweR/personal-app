<?php

declare(strict_types=1);

namespace App\Modules\User\Application\DTOs;

use Spatie\LaravelData\Data;

final class EmailChannelInfoDTO extends Data
{
    public function __construct(
        public readonly ?string $address,
        public readonly bool $verified,
    ) {}
}
