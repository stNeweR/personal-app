<?php

declare(strict_types=1);

namespace App\Modules\Plugin\Infrastructure\Http\V1\Controllers;

use App\Modules\Plugin\Application\Services\PluginDiscoveryService;
use App\Modules\Plugin\Infrastructure\Models\Plugin;
use App\Modules\User\Domain\Enums\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function enable(Request $request, string $name): JsonResponse
    {
        /** @var \App\Modules\User\Infrastructure\Models\User|null $user */
        $user = $request->user();
        $plan = $user->plan ?? Plan::Junior;

        if ($plan === Plan::Junior) {
            return response()->json(['message' => 'План Junior не позволяет активировать плагины. Поменяйте план.'], 403);
        }

        if ($plan === Plan::Middle) {
            $enabledCount = Plugin::where('enabled', true)->count();
            if ($enabledCount >= 2) {
                return response()->json(['message' => 'План Middle позволяет активировать максимум 2 плагина. Поменяйте план.'], 403);
            }
        }

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
