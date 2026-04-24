<?php

namespace App\Modules\Pomodoro\Application\Handlers\Command;

use App\Core\Telegram\Application\Handlers\Command\CommandHandlerDTO;
use App\Core\Telegram\Application\Handlers\Command\CommandHandlerInterface;
use App\Modules\Pomodoro\Application\DTOs\GetTodaySessionsDTO;
use App\Modules\Pomodoro\Application\UseCases\GetTodaySessionsUseCase;

final readonly class GetTodaySessionsHandler implements CommandHandlerInterface
{
    public function __construct(
        private GetTodaySessionsUseCase $useCase
    ) {}

    public function handle(CommandHandlerDTO $data): void
    {
        $this->useCase->execute(new GetTodaySessionsDTO(telegramId: $data->telegramId));
    }
}
