<?php

declare(strict_types=1);

namespace Plugins\Telegram\Tests\Doubles;

use App\Core\Telegram\Domain\Contracts\TelegramApiClientInterface;
use App\Core\Telegram\Infrastructure\Services\Telegram\DTOs\SendMessageDTO;
use App\Core\Telegram\Infrastructure\Services\Telegram\DTOs\TelegramApiResponse;

final class RecordingTelegramApiClient implements TelegramApiClientInterface
{
    /**
     * @var list<array{action: string, data: array<string, mixed>}>
     */
    public array $calls = [];

    public function sendMessage(SendMessageDTO $dto): TelegramApiResponse
    {
        $this->calls[] = [
            'action' => 'send_message',
            'data' => [
                'chat_id' => $dto->chatId,
                'text' => $dto->text,
                'parse_mode' => $dto->parseMode,
            ],
        ];

        return new TelegramApiResponse(ok: true, description: null, error_code: null);
    }

    public function setWebhook(): TelegramApiResponse
    {
        $this->calls[] = [
            'action' => 'set_webhook',
            'data' => [],
        ];

        return new TelegramApiResponse(ok: true, description: null, error_code: null);
    }

    public function setTelegramCommands(): TelegramApiResponse
    {
        $this->calls[] = [
            'action' => 'set_commands',
            'data' => [],
        ];

        return new TelegramApiResponse(ok: true, description: null, error_code: null);
    }

    /**
     * @return list<array{action: string, data: array<string, mixed>}>
     */
    public function callsFor(string $action): array
    {
        return array_values(array_filter(
            $this->calls,
            static fn (array $call): bool => $call['action'] === $action,
        ));
    }
}
