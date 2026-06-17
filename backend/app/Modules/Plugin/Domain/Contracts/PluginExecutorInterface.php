<?php

declare(strict_types=1);

namespace App\Modules\Plugin\Domain\Contracts;

interface PluginExecutorInterface
{
    /**
     * Execute a plugin action and return the result array.
     *
     * @param  string  $name  The plugin name (e.g. "calendar")
     * @param  string  $action  The action name (e.g. "list_events")
     * @param  array<string, mixed>  $input  JSON-decodable input payload
     * @return array<string, mixed> JSON-decodable response
     *
     * @throws \App\Modules\Plugin\Domain\Exceptions\PluginExecutionException
     */
    public function execute(string $name, string $action, array $input): array;
}
