<?php

namespace App\Core;

use App\Core\Telegram\Domain\Contracts\TelegramAdapterInterface;
use App\Core\Telegram\Domain\Contracts\TelegramApiClientInterface;
use App\Core\Telegram\Infrastructure\Adapters\TelegramAdapter;
use App\Core\Telegram\Infrastructure\Console\SetTelegramCommandsCommand;
use App\Core\Telegram\Infrastructure\Console\SetTelegramWebhookCommand;
use App\Core\Telegram\Infrastructure\Services\Telegram\TelegramApiClient;
use Illuminate\Support\ServiceProvider;

final class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TelegramAdapterInterface::class, TelegramAdapter::class);
        $this->app->bind(TelegramApiClientInterface::class, TelegramApiClient::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SetTelegramWebhookCommand::class,
                SetTelegramCommandsCommand::class,
            ]);
        }
    }
}
