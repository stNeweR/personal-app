<?php

namespace App\Core\Telegram\Application\UseCases;

use App\Core\Telegram\Application\DTOs\TelegramUpdateDTO;
use App\Core\Telegram\Application\Handlers\Command\HandlerDispatcher;

final readonly class TelegramWebhookUpdateUseCase
{
    public function __construct(
        public HandlerDispatcher $handlerDispatcher,
    ) {}

    public function execute(TelegramUpdateDTO $data): void
    {
        $this->handlerDispatcher->dispatch($data);
    }
}
