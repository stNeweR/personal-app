<?php

namespace App\Modules\Pomodoro\Application\Handlers\Command;

use App\Core\Telegram\Application\Handlers\Command\CommandHandlerDTO;
use App\Core\Telegram\Application\Handlers\Command\CommandHandlerInterface;
use App\Modules\Pomodoro\Application\DTOs\StartPomodoroDTO;
use App\Modules\Pomodoro\Application\UseCases\StartPomodoroUseCase;

final readonly class StartPomodoroHandler implements CommandHandlerInterface
{
    public function __construct(
        private StartPomodoroUseCase $useCase
    ) {}

    public function handle(CommandHandlerDTO $data): void
    {
        $this->useCase->execute(new StartPomodoroDTO(telegramId: $data->telegramId));
    }
}
