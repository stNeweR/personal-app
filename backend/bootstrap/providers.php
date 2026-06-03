<?php

use App\AppServiceProvider;
use App\Core\CoreServiceProvider;
use App\Modules\Plugin\PluginServiceProvider;
use App\Modules\Pomodoro\PomodoroServiceProvider;
use App\Modules\User\UserServiceProvider;
use Laravel\Sanctum\SanctumServiceProvider;

return [
    AppServiceProvider::class,
    CoreServiceProvider::class,
    UserServiceProvider::class,
    PomodoroServiceProvider::class,
    PluginServiceProvider::class,
    SanctumServiceProvider::class,
];
