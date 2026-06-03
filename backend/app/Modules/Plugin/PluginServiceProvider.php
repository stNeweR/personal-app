<?php

declare(strict_types=1);

namespace App\Modules\Plugin;

use App\Modules\Plugin\Domain\Contracts\PluginExecutorInterface;
use App\Modules\Plugin\Infrastructure\Adapters\GoFfiPluginExecutor;
use Illuminate\Support\ServiceProvider;

final class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PluginExecutorInterface::class, GoFfiPluginExecutor::class);
    }
}
