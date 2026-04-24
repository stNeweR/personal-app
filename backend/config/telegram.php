<?php

declare(strict_types=1);

use App\Modules\Pomodoro\Application\Handlers\Command\AddPomodoroSettingsHandler;
use App\Modules\Pomodoro\Application\Handlers\Command\GetPomodoroSettingsHandler;
use App\Modules\Pomodoro\Application\Handlers\Command\GetTodaySessionsHandler;
use App\Modules\Pomodoro\Application\Handlers\Command\StartPomodoroHandler;
use App\Modules\User\Application\Handlers\Command\StartCommandHandler;

return [
    'telegram_bot_token' => env('TELEGRAM_BOT_TOKEN'),

    'application_webhook_endpoint' => env('APPLICATION_WEBHOOK_ENDPOINT', ''),

    'telegram_url' => env('TELEGRAM_URL', ''),

    'commands_handler' => [
        'start' => StartCommandHandler::class,
        'addpomosettings' => AddPomodoroSettingsHandler::class,
        'getpomosettings' => GetPomodoroSettingsHandler::class,
        'startpomodoro' => StartPomodoroHandler::class,
        'getsessions' => GetTodaySessionsHandler::class,
    ],

    'commands_info' => [
        [
            'command' => 'start',
            'description' => 'Начать работу с ботом',
        ],
        [
            'command' => 'addpomosettings',
            'description' => 'Добавить настройки Pomodoro',
        ],
        [
            'command' => 'getpomosettings',
            'description' => 'Получить настройки Pomodoro',
        ],
        [
            'command' => 'startpomodoro',
            'description' => 'Начать Pomodoro сессию',
        ],
        [
            'command' => 'getsessions',
            'description' => 'Получить список сессий за сегодня',
        ],
    ],
];
