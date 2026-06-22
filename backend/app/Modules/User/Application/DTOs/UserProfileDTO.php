<?php

declare(strict_types=1);

namespace App\Modules\User\Application\DTOs;

use Spatie\LaravelData\Data;

final class UserProfileDTO extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $plan,
        public readonly string $name,
        public readonly string $email,
    ) {}
}
