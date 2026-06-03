<?php

declare(strict_types=1);

namespace App\Modules\Plugin\Infrastructure\Adapters;

use App\Modules\Plugin\Domain\Contracts\PluginExecutorInterface;
use App\Modules\Plugin\Domain\Exceptions\PluginExecutionException;
use FFI;
use FFI\CData;

final class GoFfiPluginExecutor implements PluginExecutorInterface
{
    private readonly FFI $ffi;

    private readonly string $libraryPath;

    public function __construct()
    {
        if (! extension_loaded('ffi')) {
            throw PluginExecutionException::ffiNotAvailable();
        }

        /** @var string $path */
        $path = config('plugins.so_path');
        $this->libraryPath = $path;

        if (! file_exists($this->libraryPath)) {
            throw PluginExecutionException::libraryNotFound($this->libraryPath);
        }

        $this->ffi = FFI::cdef(<<<'C'
            char* plugin_execute(const char* plugin_name, const char* action, const char* json_input);
            void plugin_free(char* ptr);
        C, $this->libraryPath);
    }

    public function execute(string $name, string $action, array $input): array
    {
        $jsonInput = json_encode($input, JSON_THROW_ON_ERROR);

        /** @var CData|null $resultPtr */
        $resultPtr = $this->ffi->plugin_execute($name, $action, $jsonInput);

        if ($resultPtr === null) {
            throw PluginExecutionException::fromError('null response from plugin library');
        }

        try {
            $responseJson = FFI::string($resultPtr);
            /** @var array<string, mixed> $response */
            $response = json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);
        } finally {
            $this->ffi->plugin_free($resultPtr);
        }

        if (! isset($response['success']) || $response['success'] !== true) {
            /** @var string $error */
            $error = $response['error'] ?? 'Unknown plugin error';
            throw PluginExecutionException::fromError($error);
        }

        /** @var array<string, mixed> $data */
        $data = $response['data'] ?? [];

        return $data;
    }
}
