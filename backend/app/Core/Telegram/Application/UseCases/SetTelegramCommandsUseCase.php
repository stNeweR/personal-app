<?php

namespace App\Core\Telegram\Application\UseCases;

use App\Core\Telegram\Domain\Contracts\TelegramApiClientInterface;

final readonly class SetTelegramCommandsUseCase
{
    public function __construct(
        private TelegramApiClientInterface $telegramApiClient
    ) {}

    public function execute(): void
    {
        $response = $this->telegramApiClient->setTelegramCommands();

        if (! $response->ok) {
            throw new \Exception('Failed to set Telegram commands: '.($response->description ?? 'Unknown error'));
        }
    }
}
