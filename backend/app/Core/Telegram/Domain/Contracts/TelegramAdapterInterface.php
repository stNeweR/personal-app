<?php

namespace App\Core\Telegram\Domain\Contracts;

interface TelegramAdapterInterface
{
    public function sendMessage(int $chatId, string $text, string $parseMode = 'MarkdownV2'): void;
}
