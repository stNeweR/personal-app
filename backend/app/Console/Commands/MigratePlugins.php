<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Plugin\Application\Services\PluginDiscoveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class MigratePlugins extends Command
{
    protected $signature = 'migrate:plugins {--fresh : Drop all tables and re-run}';

    protected $description = 'Run migrations from all plugins';

    public function handle(PluginDiscoveryService $discoveryService): int
    {
        $pluginsPath = $discoveryService->getPluginsPath();

        if (! File::isDirectory($pluginsPath)) {
            $this->warn('Plugins directory not found');

            return self::SUCCESS;
        }

        /** @var list<string> $migrationsPaths */
        $migrationsPaths = [];

        /** @var list<string> $directories */
        $directories = File::directories($pluginsPath);

        foreach ($directories as $pluginDir) {
            $migrationsDir = $pluginDir.'/backend/Migrations';

            if (File::isDirectory($migrationsDir)) {
                $migrationsPaths[] = $migrationsDir;
            }
        }

        if (empty($migrationsPaths)) {
            $this->info('No plugin migrations found');

            return self::SUCCESS;
        }

        $this->info('Running plugin migrations...');

        foreach ($migrationsPaths as $path) {
            $this->info("  - {$path}");
        }

        $args = [
            '--path' => $migrationsPaths,
            '--realpath' => true,
            '--force' => true,
        ];

        if ($this->option('fresh')) {
            $this->call('migrate:fresh', $args);
        } else {
            $this->call('migrate', $args);
        }

        return self::SUCCESS;
    }
}
