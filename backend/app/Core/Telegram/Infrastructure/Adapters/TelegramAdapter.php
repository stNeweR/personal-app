<?php

namespace App\Core\Telegram\Infrastructure\Adapters;

use App\Core\Telegram\Domain\Contracts\TelegramAdapterInterface;
use App\Core\Telegram\Domain\Contracts\TelegramApiClientInterface;
use App\Core\Telegram\Infrastructure\Services\Telegram\DTOs\SendMessageDTO;

class TelegramAdapter implements TelegramAdapterInterface
{
    public function __construct(
        private readonly TelegramApiClientInterface $telegramApiClient
    ) {}

    public function sendMessage(int $chatId, string $text, string $parseMode = 'HTML'): void
    {
        $this->telegramApiClient->sendMessage(new SendMessageDTO(
            chatId: $chatId,
            text: $text,
            parseMode: $parseMode
        ));
    }
}
