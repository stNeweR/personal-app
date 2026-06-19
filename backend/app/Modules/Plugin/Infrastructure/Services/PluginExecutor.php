<?php

declare(strict_types=1);

namespace App\Modules\Plugin\Infrastructure\Services;

use App\Modules\Plugin\Domain\Contracts\PluginExecutorInterface;
use App\Modules\Plugin\Domain\Exceptions\PluginExecutionException;
use Illuminate\Support\Facades\Http;

final class PluginExecutor implements PluginExecutorInterface
{
    public function execute(string $pluginName, string $action, array $input = []): array
    {
        // Try to find PHP plugin first
        $phpPlugin = $this->resolvePhpPlugin($pluginName);
        
        if ($phpPlugin !== null) {
            return $this->executePhpPlugin($phpPlugin, $action, $input);
        }
        
        // Fall back to Go plugin via HTTP
        return $this->executeGoPlugin($pluginName, $action, $input);
    }
    
    private function resolvePhpPlugin(string $pluginName): ?object
    {
        $className = 'Plugins\\' . $this->toPascalCase($pluginName) . '\\Plugin';
        
        if (!class_exists($className)) {
            return null;
        }
        
        return new $className();
    }
    
    private function executePhpPlugin(object $plugin, string $action, array $input): array
    {
        if (!method_exists($plugin, 'execute')) {
            throw new PluginExecutionException("Plugin does not have execute method");
        }
        
        $result = $plugin->execute($action, json_encode($input));
        
        if ($result === null) {
            return [];
        }
        
        if (is_array($result)) {
            return $result;
        }
        
        return (array) $result;
    }
    
    private function executeGoPlugin(string $pluginName, string $action, array $input): array
    {
        $goPluginUrl = config('plugins.go_executor_url', 'http://localhost:8080');
        
        $response = Http::post($goPluginUrl . '/execute', [
            'plugin' => $pluginName,
            'action' => $action,
            'input' => $input,
        ]);
        
        if ($response->failed()) {
            throw new PluginExecutionException("Go plugin execution failed: " . $response->body());
        }
        
        return $response->json() ?? [];
    }
    
    private function toPascalCase(string $string): string
    {
        return str_replace('_', '', ucwords($string, '_'));
    }
}