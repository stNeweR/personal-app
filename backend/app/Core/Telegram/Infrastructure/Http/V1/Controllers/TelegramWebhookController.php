<?php

namespace App\Core\Telegram\Infrastructure\Http\V1\Controllers;

use App\Core\Telegram\Application\DTOs\TelegramUpdateDTO;
use App\Core\Telegram\Application\UseCases\TelegramWebhookUpdateUseCase;
use App\Core\Telegram\Infrastructure\Http\V1\Requests\TelegramWebhookRequest;

final class TelegramWebhookController
{
    public function handleWebhook(TelegramWebhookRequest $request, TelegramWebhookUpdateUseCase $handler): void
    {
        $validatedData = $request->validated();

        /** @var array{
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
         * } $validatedData */
        $handler->execute(
            TelegramUpdateDTO::fromArray($validatedData)
        );
    }
}
