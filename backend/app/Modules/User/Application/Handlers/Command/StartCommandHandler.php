<?php

namespace App\Modules\User\Application\Handlers\Command;

use App\Core\Telegram\Application\Handlers\Command\CommandHandlerDTO;
use App\Core\Telegram\Application\Handlers\Command\CommandHandlerInterface;
use App\Modules\User\Application\UseCases\CreateTelegramUserUseCase;
use Illuminate\Support\Facades\Log;

final readonly class StartCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CreateTelegramUserUseCase $createHandler,
        private LinkTelegramHandler $linkHandler,
    ) {}

    public function handle(CommandHandlerDTO $data): void
    {
        Log::info('test');
        $parts = explode(' ', trim((string) $data->message));

        if (count($parts) > 1 && $parts[1] !== '') {
            $this->linkHandler->handle($data);

            return;
        }

        $this->createHandler->execute($data);
    }
}
