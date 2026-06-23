<?php

namespace Tests\Assertions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

trait TelegramAssertion
{
    public function getTelegramRequests(?string $endpoint = null): Collection
    {
        $telegramUrl = config('telegram.telegram_url');
        $botToken = config('telegram.telegram_bot_token');

        $allRequests = Http::recorded();

        if ($endpoint === null) {
            return collect($allRequests)
                ->filter(
                    fn ($pair) => str_contains(
                        $pair[0]->url(),
                        '/bot'.$botToken,
                    ),
                )
                ->map(fn ($pair) => $pair[0]);
        }

        $fullUrl = $telegramUrl.'/bot'.$botToken.$endpoint;

        return collect($allRequests)
            ->filter(fn ($pair) => $pair[0]->url() === $fullUrl)
            ->map(fn ($pair) => $pair[0]);
    }

    public function assertTelegramRequestSent(
        string $endpoint,
        array $expectedData = [],
    ): void {
        $telegramUrl = config('telegram.telegram_url');
        $botToken = config('telegram.telegram_bot_token');
        $fullUrl = $telegramUrl.'/bot'.$botToken.$endpoint;

        Http::assertSent(function ($request) use ($fullUrl, $expectedData) {
            if ($request->url() !== $fullUrl) {
                return false;
            }

            foreach ($expectedData as $key => $value) {
                if ($request[$key] !== $value) {
                    return false;
                }
            }

            return true;
        });
    }

    public function assertTelegramMessageSent(
        int $chatId,
        ?string $text = null,
    ): void {
        $telegramUrl = config('telegram.telegram_url');
        $botToken = config('telegram.telegram_bot_token');
        $fullUrl = $telegramUrl.'/bot'.$botToken.'/sendMessage';

        Http::assertSent(function ($request) use ($fullUrl, $chatId, $text) {
            if ($request->url() !== $fullUrl) {
                return false;
            }

            if ($request['chat_id'] !== $chatId) {
                return false;
            }

            if ($text !== null && $request['text'] !== $text) {
                return false;
            }

            return true;
        });
    }

    public function assertTelegramMessageContains(string $text): void
    {
        $telegramUrl = config('telegram.telegram_url');
        $botToken = config('telegram.telegram_bot_token');
        $fullUrl = $telegramUrl.'/bot'.$botToken.'/sendMessage';

        Http::assertSent(function ($request) use ($fullUrl, $text) {
            if ($request->url() !== $fullUrl) {
                return false;
            }

            return str_contains($request['text'] ?? '', $text);
        });
    }
}
