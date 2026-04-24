<?php

namespace App\Modules\Pomodoro\Application\UseCases;

use App\Core\Telegram\Domain\Contracts\TelegramAdapterInterface;
use App\Modules\Pomodoro\Application\DTOs\UseCaseStateHandlerDTO;
use App\Modules\Pomodoro\Domain\Enums\StateValue;
use App\Modules\Pomodoro\Domain\Repository\PomodoroSettingsRepositoryInterface;
use App\Modules\User\Domain\Contracts\UserAdapterInterface;

final readonly class AddWorkDurationUseCase
{
    public function __construct(
        private PomodoroSettingsRepositoryInterface $pomodoroSettingsRepository,
        private UserAdapterInterface $userAdapter,
        private TelegramAdapterInterface $telegramAdapter
    ) {}

    public function execute(UseCaseStateHandlerDTO $data): void
    {
        $user = $this->userAdapter->getUserByTelegramId($data->telegramId);

        $settings = $this->pomodoroSettingsRepository->getByUserId($user->id);

        if (is_null($settings)) {
            $this->pomodoroSettingsRepository->create($user->id, (int) $data->message);

            $this->telegramAdapter->sendMessage($user->telegram_id, __('pomodoro.work_duration_saved'));
        } else {
            $this->pomodoroSettingsRepository->update($user->id, 'work_duration', (int) $data->message);

            $this->telegramAdapter->sendMessage($user->telegram_id, __('pomodoro.work_duration_updated'));
        }
        $this->userAdapter->updateUserState($user->telegram_id, StateValue::AWAITING_BREAK_DURATION);
    }
}
