<?php

namespace App\Modules\User\Application\UseCases;

use App\Core\Telegram\Application\Handlers\Command\CommandHandlerDTO;
use App\Core\Telegram\Domain\Contracts\TelegramAdapterInterface;
use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class CreateTelegramUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TelegramAdapterInterface $telegramAdapter
    ) {}

    public function execute(CommandHandlerDTO $data): void
    {
        try {
            $this->userRepository->getByTelegramId($data->telegramId);

            $this->telegramAdapter->sendMessage(
                $data->telegramId,
                __('user.already_registered')
            );
        } catch (ModelNotFoundException $e) {
            $this->userRepository->createUser($data->telegramId);

            $this->telegramAdapter->sendMessage(
                $data->telegramId,
                __('user.welcome')
            );
        }
    }
}
