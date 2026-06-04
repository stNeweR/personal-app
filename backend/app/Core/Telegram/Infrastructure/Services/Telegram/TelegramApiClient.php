<?php

declare(strict_types=1);

namespace App\Core\Telegram\Infrastructure\Services\Telegram;

use App\Core\Telegram\Domain\Contracts\TelegramApiClientInterface;
use App\Core\Telegram\Infrastructure\Services\Telegram\DTOs\SendMessageDTO;
use App\Core\Telegram\Infrastructure\Services\Telegram\DTOs\TelegramApiResponse;
use App\Modules\Plugin\Domain\Contracts\PluginExecutorInterface;
use Illuminate\Support\Facades\Config;

final class TelegramApiClient implements TelegramApiClientInterface
{
    public function __construct(
        private readonly PluginExecutorInterface $pluginExecutor,
    ) {}

    /**
     * @return array{bot_token: string, api_url: string}
     */
    private function authInput(): array
    {
        return [
            'bot_token' => Config::string('telegram.telegram_bot_token'),
            'api_url' => Config::string('telegram.telegram_url'),
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     */
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

    public function setWebhook(): TelegramApiResponse
    {
        $url = Config::string('telegram.webhook_url');

        if ($url === '') {
            $url = Config::string('app.url').'/'.Config::string('telegram.application_webhook_endpoint');
        }

        $result = $this->pluginExecutor->execute('telegram', 'set_webhook', [
            ...$this->authInput(),
            'url' => $url,
        ]);

        return $this->responseFromResult($result);
    }

    public function sendMessage(SendMessageDTO $dto): TelegramApiResponse
    {
        $result = $this->pluginExecutor->execute('telegram', 'send_message', [
            ...$this->authInput(),
            'chat_id' => $dto->chatId,
            'text' => $dto->text,
            'parse_mode' => $dto->parseMode,
        ]);

        return $this->responseFromResult($result);
    }

    public function setTelegramCommands(): TelegramApiResponse
    {
        /** @var list<array<string, string>> $commands */
        $commands = Config::get('telegram.commands_info', []);

        $result = $this->pluginExecutor->execute('telegram', 'set_commands', [
            ...$this->authInput(),
            'commands' => $commands,
        ]);

        return $this->responseFromResult($result);
    }
}
