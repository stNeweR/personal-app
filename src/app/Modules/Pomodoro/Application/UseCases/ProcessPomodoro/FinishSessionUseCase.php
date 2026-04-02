<?php

namespace App\Modules\Pomodoro\Application\UseCases\ProcessPomodoro;

use App\Core\Telegram\Domain\Contracts\TelegramAdapterInterface;
use App\Modules\Pomodoro\Domain\Repository\PomodoroSessionsRepositoryInterface;
use Illuminate\Support\Facades\Log;

final readonly class FinishSessionUseCase
{
    public function __construct(
        private PomodoroSessionsRepositoryInterface $pomodoroSessionsRepository,
        private TelegramAdapterInterface $telegramAdapter
    ) {}

    public function handle(int $sessionId, int $chatId): void
    {
        $this->pomodoroSessionsRepository->endSession($sessionId);

        Log::info('finish');
        $this->telegramAdapter->sendMessage(
            chatId: $chatId,
            text: __('pomodoro.pomodoro_completed')
        );
    }
}
