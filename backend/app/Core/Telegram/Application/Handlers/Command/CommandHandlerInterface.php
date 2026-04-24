<?php

namespace App\Core\Telegram\Application\Handlers\Command;

interface CommandHandlerInterface
{
    public function handle(CommandHandlerDTO $data): void;
}
