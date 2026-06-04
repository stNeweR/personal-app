<?php

declare(strict_types=1);

namespace Tests\SetUps;

use App\Core\Telegram\Domain\Contracts\TelegramApiClientInterface;
use Tests\Doubles\RecordingTelegramApiClient;

trait SetupTelegram
{
    public RecordingTelegramApiClient $telegramRecorder;

    public function setupTelegramApi(): void
    {
        $this->telegramRecorder = new RecordingTelegramApiClient;
        $this->app->instance(TelegramApiClientInterface::class, $this->telegramRecorder);
    }
}
