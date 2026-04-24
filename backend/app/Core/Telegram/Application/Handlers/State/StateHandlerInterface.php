<?php

namespace App\Core\Telegram\Application\Handlers\State;

interface StateHandlerInterface
{
    public function handle(StateHandlerDTO $data): void;
}
