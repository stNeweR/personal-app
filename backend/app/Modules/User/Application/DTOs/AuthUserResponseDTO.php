<?php

declare(strict_types=1);

namespace App\Modules\User\Application\DTOs;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class AuthUserResponseDTO extends Data
{
    /**
     * @param  array{id: int, name: string|null, email: string|null}  $user
     */
    public function __construct(
        public readonly string $token,
        public readonly array $user,
    ) {}
}
