<?php

namespace App\Modules\Pomodoro\Application\Handlers\State;

use App\Core\Telegram\Application\Handlers\State\StateHandlerDTO;
use App\Core\Telegram\Application\Handlers\State\StateHandlerInterface;
use App\Modules\Pomodoro\Application\DTOs\UseCaseStateHandlerDTO;
use App\Modules\Pomodoro\Application\UseCases\AddCyclesBeforeLongBreakUseCase;

final readonly class AwaitingCyclesBeforeLongBreakStateHandler implements StateHandlerInterface
{
    public function __construct(
        private AddCyclesBeforeLongBreakUseCase $useCase
    ) {}

    public function handle(StateHandlerDTO $data): void
    {
        $this->useCase->execute(UseCaseStateHandlerDTO::from($data->toArray()));
    }
}
