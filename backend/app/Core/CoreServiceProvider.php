<?php

namespace App\Core;

use App\Core\MailNotifier\Domain\Contracts\MailNotifierApiClientInterface;
use App\Core\MailNotifier\Infrastructure\Services\MailNotifier\MailNotifierApiClient;
use Illuminate\Support\ServiceProvider;

final class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MailNotifierApiClientInterface::class, MailNotifierApiClient::class);
    }
}
