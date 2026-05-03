<?php

declare(strict_types=1);

namespace App\Modules\User\Application\DTOs;

use Spatie\LaravelData\Data;

final class LoginUserDTO extends Data
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {}
}
