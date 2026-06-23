<?php

declare(strict_types=1);

namespace Plugins\Telegram\Tests\SetUps;

use App\Core\Telegram\Domain\Contracts\TelegramApiClientInterface;
use Plugins\Telegram\Tests\Doubles\RecordingTelegramApiClient;

trait SetupTelegram
{
    public RecordingTelegramApiClient $telegramRecorder;

    public function setupTelegramApi(): void
    {
        $this->telegramRecorder = new RecordingTelegramApiClient;
        $this->app->instance(TelegramApiClientInterface::class, $this->telegramRecorder);
    }
}
