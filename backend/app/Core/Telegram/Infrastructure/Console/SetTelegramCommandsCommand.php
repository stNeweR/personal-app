<?php

namespace App\Core\Telegram\Infrastructure\Console;

use App\Core\Telegram\Application\UseCases\SetTelegramCommandsUseCase;
use Illuminate\Console\Command;

final class SetTelegramCommandsCommand extends Command
{
    protected $signature = 'telegram:set-commands';

    protected $description = 'Set telegram bot commands';

    private string $text = 'Commands set!';

    public function handle(SetTelegramCommandsUseCase $handler): int
    {
        try {
            $handler->execute();

            $this->info($this->text);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
