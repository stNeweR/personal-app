<?php

namespace App\Core\Telegram\Infrastructure\Services\Telegram;

use App\Core\Telegram\Domain\Contracts\TelegramApiClientInterface;
use App\Core\Telegram\Infrastructure\Services\Telegram\DTOs\SendMessageDTO;
use App\Core\Telegram\Infrastructure\Services\Telegram\DTOs\TelegramApiResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

final class TelegramApiClient implements TelegramApiClientInterface
{
    private string $token;

    private string $telegramUrl;

    private string $telegramApiUrl;

    public function __construct()
    {
        $this->token = Config::string('telegram.telegram_bot_token');
        $this->telegramUrl = Config::string('telegram.telegram_url');
        $this->telegramApiUrl = $this->telegramUrl.'/bot'.$this->token;
    }

    public function setWebhook(): TelegramApiResponse
    {
        $applicationEndpoint = Config::string('app.url').'/'.Config::string('telegram.application_webhook_endpoint');

        $response = Http::post($this->telegramApiUrl.'/setWebhook', [
            'url' => $applicationEndpoint,
        ]);

        return TelegramApiResponse::from($response->json());
    }

    public function sendMessage(SendMessageDTO $dto): TelegramApiResponse
    {
        $response = Http::post($this->telegramApiUrl.'/sendMessage', $dto->toArray());

        return TelegramApiResponse::from($response->json());
    }

    public function setTelegramCommands(): TelegramApiResponse
    {
        $commands = Config::get('telegram.commands_info', []);

        $response = Http::post($this->telegramApiUrl.'/setMyCommands', [
            'commands' => $commands,
        ]);

        return TelegramApiResponse::from($response->json());
    }
}
