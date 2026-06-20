<?php

declare(strict_types=1);

return [
    'telegram_bot_token' => env('TELEGRAM_BOT_TOKEN'),

    'telegram_bot_name' => env('TELEGRAM_BOT_NAME', ''),

    'application_webhook_endpoint' => env('APPLICATION_WEBHOOK_ENDPOINT', ''),

    'webhook_url' => env('TELEGRAM_WEBHOOK_URL', ''),

    'telegram_url' => env('TELEGRAM_URL', ''),

    'commands_handler' => [
        'start' => \App\Modules\User\Application\Handlers\Command\StartCommandHandler::class,
        'register' => \App\Modules\User\Application\Handlers\Command\LinkTelegramHandler::class,
        'addpomosettings' => \App\Modules\Pomodoro\Application\Handlers\Command\AddPomodoroSettingsHandler::class,
        'getpomosettings' => \App\Modules\Pomodoro\Application\Handlers\Command\GetPomodoroSettingsHandler::class,
        'startpomodoro' => \App\Modules\Pomodoro\Application\Handlers\Command\StartPomodoroHandler::class,
        'getsessions' => \App\Modules\Pomodoro\Application\Handlers\Command\GetTodaySessionsHandler::class,
    ],

    'commands_info' => [
        [
            'command' => 'start',
            'description' => 'Начать работу с ботом',
        ],
        [
            'command' => 'register',
            'description' => 'Привязать аккаунт к веб-приложению',
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
