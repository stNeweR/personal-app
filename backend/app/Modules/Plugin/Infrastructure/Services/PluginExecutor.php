<?php

declare(strict_types=1);

namespace App\Modules\Plugin\Infrastructure\Services;

use App\Modules\Plugin\Domain\Contracts\PluginExecutorInterface;
use App\Modules\Plugin\Domain\Exceptions\PluginExecutionException;

final class PluginExecutor implements PluginExecutorInterface
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function execute(string $pluginName, string $action, array $input = []): array
    {
        $phpPlugin = $this->resolvePhpPlugin($pluginName);

        if ($phpPlugin !== null) {
            return $this->executePhpPlugin($phpPlugin, $action, $input);
        }

        throw new PluginExecutionException("Plugin \"{$pluginName}\" not found");
    }

    private function resolvePhpPlugin(string $pluginName): ?object
    {
        $className = 'Plugins\\'.$this->toPascalCase($pluginName).'\\Plugin';

        if (! class_exists($className)) {
            return null;
        }

        return new $className;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function executePhpPlugin(object $plugin, string $action, array $input): array
    {
        if (! method_exists($plugin, 'execute')) {
            throw new PluginExecutionException('Plugin does not have execute method');
        }

        $result = $plugin->execute($action, json_encode($input));

        if ($result === null) {
            return [];
        }

        if (is_array($result)) {
            /** @var array<string, mixed> $result */
            return $result;
        }

        /** @var array<string, mixed> $arrayResult */
        $arrayResult = (array) $result;

        return $arrayResult;
    }

    private function toPascalCase(string $string): string
    {
        return str_replace('_', '', ucwords($string, '_'));
    }
}
