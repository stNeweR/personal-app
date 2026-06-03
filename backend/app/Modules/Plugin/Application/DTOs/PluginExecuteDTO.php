<?php

declare(strict_types=1);

namespace App\Modules\Plugin\Application\DTOs;

use Spatie\LaravelData\Data;

final class PluginExecuteDTO extends Data
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function __construct(
        public readonly string $name,
        public readonly string $action,
        public readonly array $input = [],
    ) {}
}
