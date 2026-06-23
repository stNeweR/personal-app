<?php

namespace App\Core\Telegram\Infrastructure\Services\Telegram;

use App\Core\Telegram\Domain\Contracts\TelegramApiClientInterface;
use App\Core\Telegram\Infrastructure\Services\Telegram\DTOs\SendMessageDTO;
use App\Core\Telegram\Infrastructure\Services\Telegram\DTOs\TelegramApiResponse;
<<<<<<< HEAD
=======
use Illuminate\Support\Facades\Http;
>>>>>>> course
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

final class TelegramApiClient implements TelegramApiClientInterface
{
<<<<<<< HEAD
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

        $response = Http::timeout(30)->post($this->telegramApiUrl.'/setWebhook', [
            'url' => $applicationEndpoint,
        ]);

        return TelegramApiResponse::from($response->json());
    }

    public function sendMessage(SendMessageDTO $dto): TelegramApiResponse
    {
        $response = Http::timeout(10)->post($this->telegramApiUrl.'/sendMessage', $dto->toArray());

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
=======
    private function baseURL(): string
    {
        $token = Config::string('telegram.telegram_bot_token');
        $apiUrl = Config::string('telegram.telegram_url', 'https://api.telegram.org');
        
        return rtrim($apiUrl, '/') . '/bot' . $token;
    }

    private function makeRequest(string $method, array $payload = []): array
    {
        $url = $this->baseURL() . '/' . $method;
        
        $response = Http::post($url, $payload);
        
        if ($response->failed()) {
            return [
                'ok' => false,
                'description' => 'HTTP Error: ' . $response->status(),
            ];
        }
        
        return $response->json() ?? ['ok' => false, 'description' => 'Empty response'];
    }

    public function setWebhook(): TelegramApiResponse
    {
        $url = Config::string('telegram.webhook_url');
        
        if ($url === '') {
            $url = Config::string('app.url') . '/' . Config::string('telegram.application_webhook_endpoint');
        }

        $result = $this->makeRequest('setWebhook', [
            'url' => $url,
        ]);

        return $this->responseFromResult($result);
    }

    public function sendMessage(SendMessageDTO $dto): TelegramApiResponse
    {
        $payload = [
            'chat_id' => $dto->chatId,
            'text' => $dto->text,
        ];
        
        if ($dto->parseMode !== '') {
            $payload['parse_mode'] = $dto->parseMode;
        }

        $result = $this->makeRequest('sendMessage', $payload);

        return $this->responseFromResult($result);
    }

    public function setTelegramCommands(): TelegramApiResponse
    {
        /** @var list<array<string, string>> $commands */
        $commands = Config::get('telegram.commands_info', []);

        $result = $this->makeRequest('setMyCommands', [
            'commands' => $commands,
        ]);

        return $this->responseFromResult($result);
    }

    private function responseFromResult(array $result): TelegramApiResponse
    {
        $description = $result['description'] ?? null;
        $descriptionString = match (true) {
            $description === null => null,
            is_string($description) => $description,
            is_scalar($description) => (string) $description,
            default => null,
        };

        return new TelegramApiResponse(
            ok: (bool) ($result['ok'] ?? false),
            description: $descriptionString,
            error_code: null,
        );
    }
}
>>>>>>> course
