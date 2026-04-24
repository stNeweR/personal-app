<?php

namespace App\Core\Telegram\Application\Strategies\Command;

use App\Core\Telegram\Application\DTOs\TelegramUpdateDTO;
use App\Core\Telegram\Application\Handlers\Command\CommandHandlerDTO;
use App\Core\Telegram\Application\Handlers\Command\CommandHandlerInterface;
use App\Core\Telegram\Domain\Exceptions\UnknownCommandException;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

final class CommandStrategy
{
    /** @var array<string, class-string<CommandHandlerInterface>> */
    private array $commandsHandler = [];

    public function __construct(
        private readonly Container $container
    ) {
        /** @var array<string, class-string<CommandHandlerInterface>> */
        $config = Config::get('telegram.commands_handler', []);

        $this->commandsHandler = $config;
    }

    public function execute(string $command, TelegramUpdateDTO $data): void
    {
        try {
            $handlerClass = $this->commandsHandler[$command] ?? null;

            if (is_null($handlerClass)) {
                $userId = $data->userId ?? 0;
                throw new UnknownCommandException($command, $userId);
            }

            /** @var CommandHandlerInterface $handler */
            $handler = $this->container->make($handlerClass);

            // @phpstan-ignore instanceof.alwaysTrue
            if (! $handler instanceof CommandHandlerInterface) {
                throw new \RuntimeException(
                    sprintf('Handler %s must implement CommandHandlerInterface', $handlerClass)
                );
            }

            $telegramId = $data->userId ?? 0;
            $handler->handle(new CommandHandlerDTO(
                $data->updateId,
                $telegramId,
                $data->messageText
            ));
        } catch (\Exception $e) {
            Log::error('Failed to create command handler', [
                'handler' => $handlerClass,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
