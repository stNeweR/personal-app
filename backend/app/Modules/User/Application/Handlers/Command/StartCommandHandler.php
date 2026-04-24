<?php

namespace App\Modules\User\Application\Handlers\Command;

use App\Core\Telegram\Application\Handlers\Command\CommandHandlerDTO;
use App\Core\Telegram\Application\Handlers\Command\CommandHandlerInterface;
use App\Modules\User\Application\UseCases\CreateTelegramUserUseCase;

final readonly class StartCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CreateTelegramUserUseCase $handler
    ) {}

    public function handle(CommandHandlerDTO $data): void
    {
        $this->handler->execute($data);
    }
}
