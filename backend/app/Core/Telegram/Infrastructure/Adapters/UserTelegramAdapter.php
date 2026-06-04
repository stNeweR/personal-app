<?php

namespace App\Core\Telegram\Infrastructure\Adapters;

use App\Core\Telegram\Domain\Contracts\TelegramAdapterInterface;
use App\Core\Telegram\Domain\Contracts\TelegramApiClientInterface;
use App\Core\Telegram\Infrastructure\Services\Telegram\DTOs\SendMessageDTO;

class UserTelegramAdapter implements TelegramAdapterInterface
{
    public function __construct(
        private readonly TelegramApiClientInterface $telegramApiClient
    ) {}

    public function sendMessage(int $chatId, string $text, string $parseMode = 'MarkdownV2'): void
    {
        $this->telegramApiClient->sendMessage(new SendMessageDTO(
            chatId: $chatId,
            text: $text,
            parseMode: $parseMode
        ));
    }
}
