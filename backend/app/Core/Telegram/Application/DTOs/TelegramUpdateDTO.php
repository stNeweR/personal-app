<?php

namespace App\Core\Telegram\Application\DTOs;

use InvalidArgumentException;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class TelegramUpdateDTO extends Data
{
    public function __construct(
        public readonly int $updateId,
        public readonly int $userId,
        public readonly ?int $chatId,
        public readonly ?string $messageText,
        public readonly ?string $callbackData
    ) {}

    /**
     * @param array{
     *     update_id?: int,
     *     message?: array{
     *         from?: array{id?: int},
     *         chat?: array{id?: int},
     *         text?: string
     *     },
     *     callback_query?: array{
     *         from?: array{id?: int},
     *         message?: array{
     *             chat?: array{id?: int}
     *         },
     *         data?: string
     *     }
     * } $data
     */
    public static function fromArray(array $data): self
    {
        $updateId = isset($data['update_id']) ? (int) $data['update_id'] : 0;
        $userId = null;

        if (isset($data['message']['from']['id'])) {
            $userId = (int) $data['message']['from']['id'];
        } elseif (isset($data['callback_query']['from']['id'])) {
            $userId = (int) $data['callback_query']['from']['id'];
        }

        if (is_null($userId)) {
            throw new InvalidArgumentException('User ID is required');
        }

        $chatId = null;
        if (isset($data['message']['chat']['id'])) {
            $chatId = (int) $data['message']['chat']['id'];
        } elseif (isset($data['callback_query']['message']['chat']['id'])) {
            $chatId = (int) $data['callback_query']['message']['chat']['id'];
        }

        $messageText = null;
        if (isset($data['message']['text'])) {
            $messageText = (string) $data['message']['text'];
        }

        $callbackData = null;
        if (isset($data['callback_query']['data'])) {
            $callbackData = (string) $data['callback_query']['data'];
        }

        return new self(
            updateId: $updateId,
            userId: $userId,
            chatId: $chatId,
            messageText: $messageText,
            callbackData: $callbackData
        );
    }

    public function getCommand(): ?string
    {
        if (! $this->messageText) {
            return null;
        }

        $text = trim($this->messageText);

        if (! str_starts_with($text, '/')) {
            return null;
        }

        $parts = explode(' ', $text, 2);
        $commandWithBot = $parts[0];

        $command = explode('@', $commandWithBot)[0];

        return strtolower(substr($command, 1));
    }
}
