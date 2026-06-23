<?php

declare(strict_types=1);

namespace App\Modules\Plugin\Application\Services;

use App\Modules\Plugin\Domain\Entities\PluginManifest;
use App\Modules\Plugin\Infrastructure\Models\Plugin;
use Illuminate\Support\Facades\File;

final class PluginDiscoveryService
{
    private readonly string $pluginsPath;

    public function __construct()
    {
        $this->pluginsPath = $this->resolvePluginsPath();
    }

    public function getPluginsPath(): string
    {
        return $this->pluginsPath;
    }

    public function getPluginPath(string $name): string
    {
        return $this->pluginsPath.'/'.$name;
    }

    /**
     * @return array<string, PluginManifest>
     */
    public function discover(): array
    {
        $manifests = [];

        if (! File::isDirectory($this->pluginsPath)) {
            return $manifests;
        }

        $directories = File::directories($this->pluginsPath);

        foreach ($directories as $directory) {
            $manifestPath = $directory.'/plugin.json';

            if (! File::exists($manifestPath)) {
                continue;
            }

            $content = File::get($manifestPath);
            $data = json_decode($content, true);

            if ($data === null || ! isset($data['name'])) {
                continue;
            }

            $manifests[$data['name']] = PluginManifest::fromArray($data);
        }

        return $manifests;
    }

    public function syncWithDatabase(): void
    {
        $discovered = $this->discover();
        $existing = Plugin::all()->keyBy('name');

        // Add new plugins
        foreach ($discovered as $name => $manifest) {
            if (! $existing->has($name)) {
                Plugin::create([
                    'name' => $manifest->name,
                    'version' => $manifest->version,
                    'author' => $manifest->author,
                    'description' => $manifest->description,
                    'enabled' => false,
                ]);
            }
        }

        // Remove deleted plugins
        foreach ($existing as $name => $plugin) {
            if (! isset($discovered[$name])) {
                $plugin->delete();
            }
        }
    }

    /**
     * @return array<string, PluginManifest>
     */
    public function getEnabledManifests(): array
    {
        $enabledNames = Plugin::where('enabled', true)->pluck('name')->toArray();
        $all = $this->discover();

        return array_intersect_key($all, array_flip($enabledNames));
    }

    private function resolvePluginsPath(): string
    {
        $candidates = [
            base_path('plugins'),
            base_path('../plugins'),
        ];

        foreach ($candidates as $path) {
            if ($this->hasPluginManifests($path)) {
                return $path;
            }
        }

        return base_path('../plugins');
    }

    private function hasPluginManifests(string $path): bool
    {
        if (! File::isDirectory($path)) {
            return false;
        }

        foreach (File::directories($path) as $directory) {
            if (File::exists($directory.'/plugin.json')) {
                return true;
            }
        }

        return false;
    }
}
