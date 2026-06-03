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

    public static function ffiNotAvailable(): self
    {
        return new self('PHP FFI extension is not available.');
    }

    public static function libraryNotFound(string $path): self
    {
        return new self("Plugin library not found at: {$path}");
    }
}
