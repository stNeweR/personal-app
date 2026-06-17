<?php

namespace App\Core\Telegram\Application\UseCases;

use App\Core\Telegram\Domain\Exceptions\SetWebhookException;
use App\Core\Telegram\Infrastructure\Services\Telegram\TelegramApiClient;

final readonly class SetTelegramWebhookUseCase
{
    public function __construct(
        private TelegramApiClient $telegramWebhookService,
    ) {}

    /**
     * @throws SetWebhookException
     */
    public function execute(): bool
    {
        $result = $this->telegramWebhookService->setWebhook();

        if (is_null($result->error_code) && $result->ok) {
            return $result->ok;
        }

        throw new SetWebhookException($result->description, $result->error_code);
    }
}
