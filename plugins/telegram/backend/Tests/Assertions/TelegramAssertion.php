<?php

declare(strict_types=1);

namespace Plugins\Telegram\Tests\Assertions;

use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert;

trait TelegramAssertion
{
    /**
     * @return Collection<int, mixed>
     */
    public function getTelegramRequests(?string $endpoint = null): Collection
    {
        $action = $endpoint === null || $endpoint === '/sendMessage'
            ? 'send_message'
            : ltrim($endpoint, '/');

        return collect($this->telegramRecorder->callsFor($action));
    }

    public function assertTelegramRequestSent(
        string $endpoint,
        array $expectedData = [],
    ): void {
        $requests = $this->getTelegramRequests($endpoint);

        Assert::assertTrue(
            $requests->isNotEmpty(),
            "Expected at least one Telegram request to {$endpoint}",
        );

        if ($expectedData !== []) {
            $matched = $requests->contains(function (array $call) use ($expectedData): bool {
                foreach ($expectedData as $key => $value) {
                    if (($call['data'][$key] ?? null) !== $value) {
                        return false;
                    }
                }

                return true;
            });

            Assert::assertTrue(
                $matched,
                "Expected Telegram request to {$endpoint} matching: ".json_encode($expectedData),
            );
        }
    }

    public function assertTelegramMessageSent(
        int $chatId,
        ?string $text = null,
    ): void {
        $matched = collect($this->telegramRecorder->callsFor('send_message'))
            ->contains(function (array $call) use ($chatId, $text): bool {
                if ($call['data']['chat_id'] !== $chatId) {
                    return false;
                }

                if ($text !== null && ($call['data']['text'] ?? null) !== $text) {
                    return false;
                }

                return true;
            });

        Assert::assertTrue(
            $matched,
            "Expected Telegram message to chat {$chatId}".($text !== null ? " with text '{$text}'" : ''),
        );
    }

    public function assertTelegramMessageContains(string $text): void
    {
        $matched = collect($this->telegramRecorder->callsFor('send_message'))
            ->contains(function (array $call) use ($text): bool {
                return str_contains((string) ($call['data']['text'] ?? ''), $text);
            });

        Assert::assertTrue(
            $matched,
            "Expected Telegram message containing '{$text}'",
        );
    }
}
