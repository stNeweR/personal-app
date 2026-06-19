<?php

declare(strict_types=1);

namespace Plugins\MailNotifier;

use App\Modules\Plugin\Domain\Contracts\PluginInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Plugins\MailNotifier\Services\MailNotifierService;

final class Plugin implements PluginInterface
{
    public function name(): string
    {
        return 'mail_notifier';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function register(): void
    {
        $pluginDir = __DIR__;
        spl_autoload_register(function (string $class) use ($pluginDir): void {
            $prefix = 'Plugins\\MailNotifier\\';
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
        $this->registerEventListeners();
        $this->registerRoutes();
    }

    private function registerEventListeners(): void
    {
        Event::listen(
            \App\Modules\Pomodoro\Application\Events\PomodoroPhaseChangedEvent::class,
            function (\App\Modules\Pomodoro\Application\Events\PomodoroPhaseChangedEvent $event) {
                Log::info('PomodoroPhaseChangedEvent received by mail_notifier plugin', [
                    'userId' => $event->userId,
                    'oldStatus' => $event->oldStatus,
                    'newStatus' => $event->newStatus,
                ]);

                try {
                    app(MailNotifierService::class)->handlePhaseChanged($event);
                } catch (\Throwable $e) {
                    Log::error('mail_notifier plugin failed to handle phase change', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        );
    }

    private function registerRoutes(): void
    {
        Route::middleware('auth:sanctum')
            ->prefix('api/v1/mail-notifier')
            ->group(__DIR__ . '/Routes/routes.php');
    }
}
