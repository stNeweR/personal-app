<?php

declare(strict_types=1);

namespace App\Modules\Plugin;

use App\Modules\Plugin\Application\Services\PluginDiscoveryService;
use App\Modules\Plugin\Domain\Contracts\PluginExecutorInterface;
use App\Modules\Plugin\Domain\Contracts\PluginInterface;
use App\Modules\Plugin\Infrastructure\Services\PluginExecutor;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

final class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PluginDiscoveryService::class, PluginDiscoveryService::class);
        $this->app->singleton(PluginExecutorInterface::class, PluginExecutor::class);
    }

    public function boot(): void
    {
        $discoveryService = $this->app->make(PluginDiscoveryService::class);

        $this->registerPluginMigrations($discoveryService);

        // Only sync and boot if the plugins table exists (migrations may not have run yet)
        if (Schema::hasTable('plugins')) {
            $discoveryService->syncWithDatabase();

            $enabledManifests = $discoveryService->getEnabledManifests();

            foreach ($enabledManifests as $manifest) {
                $pluginPath = $discoveryService->getPluginPath($manifest->name);
                $entryFile = $pluginPath.'/'.$manifest->backendEntry;

                if (! file_exists($entryFile)) {
                    continue;
                }

                $pluginDir = $pluginPath.'/backend';
                if (is_dir($pluginDir)) {
                    $autoloadFile = $pluginDir.'/vendor/autoload.php';
                    if (file_exists($autoloadFile)) {
                        require_once $autoloadFile;
                    }
                }

                require_once $entryFile;

                $className = $manifest->backendNamespace.'\\Plugin';

                if (! class_exists($className)) {
                    continue;
                }

                $plugin = new $className;

                if ($plugin instanceof PluginInterface) {
                    $plugin->register();
                    $plugin->boot();
                }
            }
        }
    }

    private function registerPluginMigrations(PluginDiscoveryService $discoveryService): void
    {
        $pluginsPath = $discoveryService->getPluginsPath();

        if (! is_dir($pluginsPath)) {
            return;
        }

        /** @var list<string> $migrationDirs */
        $migrationDirs = glob($pluginsPath.'/*/backend/Migrations') ?: [];

        foreach ($migrationDirs as $migrationsDir) {
            if (is_dir($migrationsDir)) {
                $this->loadMigrationsFrom($migrationsDir);
            }
        }
    }
}
