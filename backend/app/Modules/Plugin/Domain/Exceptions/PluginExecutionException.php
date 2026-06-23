<?php

declare(strict_types=1);

namespace App\Modules\Plugin\Domain\Exceptions;

use RuntimeException;

final class PluginExecutionException extends RuntimeException
{
    public static function fromError(string $error): self
    {
        return new self("Plugin execution failed: {$error}");
    }

    public static function pluginNotFound(string $name): self
    {
        return new self("Plugin not found: {$name}");
    }

    public static function manifestNotFound(string $path): self
    {
        return new self("Plugin manifest not found at: {$path}");
    }
}
