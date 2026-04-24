<?php

namespace App\Core\Telegram\Application\Strategies\State;

use App\Core\Telegram\Application\DTOs\TelegramUpdateDTO;
use App\Core\Telegram\Application\Handlers\State\StateHandlerDTO;
use App\Modules\Pomodoro\Domain\Enums\StateValue;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Log;

final class StateStrategy
{
    public function __construct(
        private readonly Container $container
    ) {}

    public function execute(StateValue $stateValue, TelegramUpdateDTO $data): void
    {
        $handlerClass = $stateValue->getHandler();

        try {
            /** @var object $handler */
            $handler = $this->container->make($handlerClass);

            if (! method_exists($handler, 'handle')) {
                throw new \RuntimeException(
                    sprintf('Handler %s must have a handle method', $handlerClass)
                );
            }

            $handler->handle(new StateHandlerDTO(
                $data->updateId,
                $data->userId,
                $data->messageText
            ));
        } catch (\Exception $e) {
            Log::error('Failed to execute state handler', [
                'state' => $stateValue->value,
                'handler' => $handlerClass,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
