<?php

namespace App\Core\Telegram\Domain\Contracts;

use App\Core\Telegram\Infrastructure\Services\Telegram\DTOs\SendMessageDTO;
use App\Core\Telegram\Infrastructure\Services\Telegram\DTOs\TelegramApiResponse;

interface TelegramApiClientInterface
{
    public function setWebhook(): TelegramApiResponse;

    public function sendMessage(SendMessageDTO $dto): TelegramApiResponse;

    public function setTelegramCommands(): TelegramApiResponse;
}
