<?php

namespace App\Core\Telegram\Infrastructure\Adapters;

use App\Core\Telegram\Domain\Contracts\TelegramAdapterInterface;
use App\Core\Telegram\Infrastructure\Services\Telegram\DTOs\SendMessageDTO;
use App\Core\Telegram\Infrastructure\Services\Telegram\TelegramApiClient;

class TelegramAdapter implements TelegramAdapterInterface
{
    public function __construct(
        private readonly TelegramApiClient $telegramApiCLient
    ) {}

    public function sendMessage(int $chatId, string $text, string $parseMode = 'MarkdownV2'): void
    {
        $this->telegramApiCLient->sendMessage(new SendMessageDTO(
            chatId: $chatId,
            text: $text,
            parseMode: $parseMode
        ));
    }
}
