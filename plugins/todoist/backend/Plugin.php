<?php

declare(strict_types=1);

namespace Plugins\Todoist;

use App\Modules\Plugin\Domain\Contracts\PluginInterface;
use Illuminate\Support\Facades\Route;

final class Plugin implements PluginInterface
{
    public function name(): string
    {
        return 'todoist';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function register(): void
    {
        $pluginDir = __DIR__;
        spl_autoload_register(function (string $class) use ($pluginDir): void {
            $prefix = 'Plugins\\Todoist\\';
            if (strpos($class, $prefix) !== 0) {
                return;
            }

            $relative = substr($class, strlen($prefix));
            $file = $pluginDir.'/'.str_replace('\\', '/', $relative).'.php';

            if (file_exists($file)) {
                require_once $file;
            }
        });
    }

    public function boot(): void
    {
        Route::middleware('auth:sanctum')
            ->prefix('api/v1/todoist')
            ->group(__DIR__.'/Routes/routes.php');
    }
}
