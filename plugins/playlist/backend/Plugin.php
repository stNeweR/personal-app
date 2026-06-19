<?php

declare(strict_types=1);

namespace Plugins\Playlist;

use App\Modules\Plugin\Domain\Contracts\PluginInterface;
use Illuminate\Support\Facades\Route;

final class Plugin implements PluginInterface
{
    public function name(): string
    {
        return 'playlist';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function register(): void
    {
        // Register plugin classes in autoloader
        $pluginDir = __DIR__;
        spl_autoload_register(function (string $class) use ($pluginDir): void {
            $prefix = 'Plugins\\Playlist\\';
            if (strpos($class, $prefix) !== 0) {
                return;
            }

            $relative = substr($class, strlen($prefix));
            $file = $pluginDir . '/' . str_replace('\\', '/', $relative) . '.php';

            if (file_exists($file)) {
                require_once $file;
            }
        });
    }

    public function boot(): void
    {
        Route::middleware('auth:sanctum')
            ->prefix('api/v1/playlist')
            ->group(__DIR__ . '/Routes/routes.php');
    }
}