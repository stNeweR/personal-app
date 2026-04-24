<?php

use App\AppServiceProvider;
use App\Core\CoreServiceProvider;
use App\Modules\Pomodoro\PomodoroServiceProvider;
use App\Modules\User\UserServiceProvider;

return [
    AppServiceProvider::class,
    CoreServiceProvider::class,
    UserServiceProvider::class,
    PomodoroServiceProvider::class,
];
