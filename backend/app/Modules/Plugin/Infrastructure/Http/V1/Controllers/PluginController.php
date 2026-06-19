<?php

declare(strict_types=1);

namespace App\Modules\Plugin\Infrastructure\Http\V1\Controllers;

use App\Modules\Plugin\Application\Services\PluginDiscoveryService;
use App\Modules\Plugin\Infrastructure\Models\Plugin;
use Illuminate\Http\JsonResponse;

final class PluginController
{
    public function __construct(
        private readonly PluginDiscoveryService $discoveryService,
    ) {}

    public function index(): JsonResponse
    {
        $this->discoveryService->syncWithDatabase();

        $plugins = Plugin::all()->map(function (Plugin $plugin) {
            $manifest = $this->discoveryService->discover()[$plugin->name] ?? null;

            return [
                'name' => $plugin->name,
                'version' => $plugin->version,
                'author' => $plugin->author,
                'description' => $plugin->description,
                'enabled' => $plugin->enabled,
                'manifest' => $manifest?->toArray(),
            ];
        });

        return response()->json(['data' => $plugins]);
    }

    public function enable(string $name): JsonResponse
    {
        $plugin = Plugin::where('name', $name)->firstOrFail();
        $plugin->update(['enabled' => true]);

        return response()->json(['data' => ['enabled' => true]]);
    }

    public function disable(string $name): JsonResponse
    {
        $plugin = Plugin::where('name', $name)->firstOrFail();
        $plugin->update(['enabled' => false]);

        return response()->json(['data' => ['enabled' => false]]);
    }

    public function getEnabledManifests(): JsonResponse
    {
        $manifests = $this->discoveryService->getEnabledManifests();

        return response()->json([
            'data' => array_values(array_map(fn ($manifest) => $manifest->toArray(), $manifests)),
        ]);
    }
}
