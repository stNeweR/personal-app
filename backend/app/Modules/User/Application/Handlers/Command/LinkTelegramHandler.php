<?php

namespace App\Modules\User\Application\Handlers\Command;

use App\Core\Telegram\Application\Handlers\Command\CommandHandlerDTO;
use App\Core\Telegram\Application\Handlers\Command\CommandHandlerInterface;
use App\Core\Telegram\Domain\Contracts\TelegramAdapterInterface;
use App\Modules\User\Application\UseCases\Auth\LinkTelegramAccountUseCase;

final readonly class LinkTelegramHandler implements CommandHandlerInterface
{
    public function __construct(
        private LinkTelegramAccountUseCase $useCase,
        private TelegramAdapterInterface $telegramAdapter,
    ) {}

    public function handle(CommandHandlerDTO $data): void
    {
        $parts = explode(' ', trim((string) $data->message));
        $token = $parts[1] ?? '';

        if ($token === '') {
            $this->telegramAdapter->sendMessage(
                chatId: $data->telegramId,
                text: __('user.link_token_missing')
            );

            return;
        }

        try {
            $this->useCase->execute($token, $data->telegramId);

            $this->telegramAdapter->sendMessage(
                chatId: $data->telegramId,
                text: __('user.telegram_linked')
            );
        } catch (\InvalidArgumentException $e) {
            $this->telegramAdapter->sendMessage(
                chatId: $data->telegramId,
                text: $e->getMessage()
            );
        }
    }
}
