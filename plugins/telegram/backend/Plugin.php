<?php

declare(strict_types=1);

namespace Plugins\Telegram;

use App\Core\Telegram\Domain\Contracts\TelegramAdapterInterface;
use App\Core\Telegram\Domain\Contracts\TelegramApiClientInterface;
use App\Core\Telegram\Infrastructure\Adapters\TelegramAdapter;
use App\Core\Telegram\Infrastructure\Services\Telegram\TelegramApiClient;
use App\Modules\Plugin\Domain\Contracts\PluginInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Plugins\Telegram\Services\TelegramNotifierService;

final class Plugin implements PluginInterface
{
    public function name(): string
    {
        return 'telegram';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function register(): void
    {
        $pluginDir = __DIR__;
        spl_autoload_register(function (string $class) use ($pluginDir): void {
            $prefix = 'Plugins\\Telegram\\';
            if (strpos($class, $prefix) !== 0) {
                return;
            }

            $relative = substr($class, strlen($prefix));
            $file = $pluginDir . '/' . str_replace('\\', '/', $relative) . '.php';

            if (file_exists($file)) {
                require_once $file;
            }
        });

        $this->mergeConfig();
    }

    public function boot(): void
    {
        $this->registerBindings();
        $this->registerEventListeners();
        $this->registerRoutes();
        $this->registerCommands();
    }

    private function mergeConfig(): void
    {
        $pluginConfig = __DIR__ . '/Config/telegram.php';
        if (file_exists($pluginConfig)) {
            $merged = array_replace_recursive(
                config('telegram', []),
                require $pluginConfig
            );
            config(['telegram' => $merged]);
        }
    }

    private function registerBindings(): void
    {
        app()->singleton(TelegramAdapterInterface::class, TelegramAdapter::class);
        app()->bind(TelegramApiClientInterface::class, TelegramApiClient::class);
    }

    private function registerEventListeners(): void
    {
        Event::listen(
            \App\Modules\Pomodoro\Application\Events\PomodoroPhaseChangedEvent::class,
            function (\App\Modules\Pomodoro\Application\Events\PomodoroPhaseChangedEvent $event) {
                Log::info('PomodoroPhaseChangedEvent received by telegram plugin', [
                    'userId' => $event->userId,
                    'oldStatus' => $event->oldStatus,
                    'newStatus' => $event->newStatus,
                ]);

                try {
                    app(TelegramNotifierService::class)->handlePhaseChanged($event);
                } catch (\Throwable $e) {
                    Log::error('telegram plugin failed to handle phase change', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        );
    }

    private function registerRoutes(): void
    {
        Route::post('api/v1/telegram-webhook', [\Plugins\Telegram\Http\Controllers\TelegramWebhookController::class, 'handleWebhook']);

        Route::middleware('auth:sanctum')
            ->prefix('api/v1/telegram-notifier')
            ->group(__DIR__ . '/Routes/routes.php');
    }

    private function registerCommands(): void
    {
        if (app()->runningInConsole()) {
            \Illuminate\Support\Facades\Artisan::command('telegram:set-webhook', function () {
                $handler = app(\App\Core\Telegram\Application\UseCases\SetTelegramWebhookUseCase::class);
                try {
                    $handler->execute();
                    $this->info('Webhook set!');
                } catch (\App\Core\Telegram\Domain\Exceptions\SetWebhookException $e) {
                    $this->error($e->getMessage());
                    return 1;
                }
                return 0;
            });

            \Illuminate\Support\Facades\Artisan::command('telegram:set-commands', function () {
                $handler = app(\App\Core\Telegram\Application\UseCases\SetTelegramCommandsUseCase::class);
                try {
                    $handler->execute();
                    $this->info('Commands set!');
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                    return 1;
                }
                return 0;
            });
        }
    }
}
