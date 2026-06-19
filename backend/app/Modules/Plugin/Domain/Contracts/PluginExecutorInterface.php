<?php

declare(strict_types=1);

namespace App\Modules\Plugin\Domain\Contracts;

interface PluginExecutorInterface
{
    /**
     * Execute a plugin action
     *
     * @param string $pluginName
     * @param string $action
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function execute(string $pluginName, string $action, array $input = []): array;
}
