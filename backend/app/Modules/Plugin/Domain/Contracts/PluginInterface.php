<?php

declare(strict_types=1);

namespace App\Modules\Plugin\Domain\Contracts;

interface PluginInterface
{
    public function name(): string;

    public function version(): string;

    public function register(): void;

    public function boot(): void;
}
