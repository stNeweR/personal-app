<?php

namespace Tests\SetUps;

use Illuminate\Support\Facades\Http;

trait SetupTelegram
{
    public function setupTelegramApi(): void
    {
        Http::fake([
            config('telegram.telegram_url').'*' => Http::response([
                'ok' => true,
            ]),
        ]);
    }
}
