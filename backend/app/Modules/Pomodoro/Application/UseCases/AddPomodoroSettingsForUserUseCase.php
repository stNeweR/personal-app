<?php

namespace App\Modules\Pomodoro\Application\UseCases;

use App\Core\Telegram\Domain\Contracts\TelegramAdapterInterface;
use App\Modules\Pomodoro\Application\DTOs\AddPomodoroSettingsDTO;
use App\Modules\Pomodoro\Domain\Enums\StateValue;
use App\Modules\User\Domain\Contracts\UserAdapterInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

final readonly class AddPomodoroSettingsForUserUseCase
{
    public function __construct(
        private UserAdapterInterface $userAdapter,
        private TelegramAdapterInterface $telegramAdapter
    ) {}

    public function execute(AddPomodoroSettingsDTO $data): void
    {
        try {
            $this->userAdapter->updateUserState($data->telegramId, StateValue::AWAITING_WORK_DURATION);

            $this->telegramAdapter->sendMessage(
                chatId: $data->telegramId,
                text: __('pomodoro.enter_work_duration')
            );
        } catch (ModelNotFoundException $e) {
            $this->telegramAdapter->sendMessage(
                chatId: $data->telegramId,
                text: __('pomodoro.try_later')
            );

            Log::error('Не удалось сохранить состояние пользователя: '.$data->telegramId.'. Ошибка - '.$e->getMessage());
        }
    }
}
