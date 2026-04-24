<?php

namespace App\Modules\Pomodoro\Application\UseCases;

use App\Core\Telegram\Domain\Contracts\TelegramAdapterInterface;
use App\Modules\Pomodoro\Application\DTOs\UseCaseStateHandlerDTO;
use App\Modules\Pomodoro\Domain\Enums\StateValue;
use App\Modules\Pomodoro\Domain\Repository\PomodoroSettingsRepositoryInterface;
use App\Modules\User\Domain\Contracts\UserAdapterInterface;

final readonly class AddRepeatsCountUseCase
{
    public function __construct(
        private PomodoroSettingsRepositoryInterface $pomodoroSettingsRepository,
        private UserAdapterInterface $userAdapter,
        private TelegramAdapterInterface $telegramAdapter
    ) {}

    public function execute(UseCaseStateHandlerDTO $data): void
    {
        $user = $this->userAdapter->getUserByTelegramId($data->telegramId);

        $this->pomodoroSettingsRepository->update($user->id, 'repeats_count', (int) $data->message);

        $this->userAdapter->updateUserState($user->telegram_id, StateValue::AWAITING_LONG_BREAK_DURATION);

        $this->telegramAdapter->sendMessage($user->telegram_id, __('pomodoro.repeats_count_saved'));
    }
}
