<?php

namespace App\Core\Telegram\Application\Handlers\Command;

use App\Core\Telegram\Application\DTOs\TelegramUpdateDTO;
use App\Core\Telegram\Application\Strategies\Command\CommandStrategy;
use App\Core\Telegram\Application\Strategies\State\StateStrategy;
use App\Modules\User\Domain\Repository\UserStateRepositoryInterface;
use Illuminate\Support\Facades\Log;

final readonly class HandlerDispatcher
{
    public function __construct(
        private CommandStrategy $commandStrategy,
        private StateStrategy $stateStrategy,
        private UserStateRepositoryInterface $userStateRepository
    ) {}

    public function dispatch(TelegramUpdateDTO $data): void
    {
        try {
            $userState = $this->userStateRepository->getUserStateByTelegramId($data->userId);
            $command = $data->getCommand();

            if ($command) {
                $this->commandStrategy->execute($command, $data);

                return;
            }

            if ($userState) {
                $this->stateStrategy->execute($userState->state_value, $data);

                return;
            }
        } catch (\Exception $exception) {
            $this->handleError($data);
            Log::warning($exception->getMessage(), $data->toArray());
        }
    }

    private function handleError(TelegramUpdateDTO $data): void
    {
        Log::error('Error in handler', [
            'user_id' => $data->userId,
        ]);
    }
}
