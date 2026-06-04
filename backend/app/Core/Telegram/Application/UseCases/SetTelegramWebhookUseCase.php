<?php

namespace App\Core\Telegram\Application\UseCases;

use App\Core\Telegram\Domain\Contracts\TelegramApiClientInterface;
use App\Core\Telegram\Domain\Exceptions\SetWebhookException;

final readonly class SetTelegramWebhookUseCase
{
    public function __construct(
        private TelegramApiClientInterface $telegramApiClient,
    ) {}

    /**
     * @throws SetWebhookException
     */
    public function execute(): bool
    {
        $result = $this->telegramApiClient->setWebhook();

        if (is_null($result->error_code) && $result->ok) {
            return $result->ok;
        }

        throw new SetWebhookException($result->description, $result->error_code);
    }
}
