<?php

namespace App\Modules\Pomodoro\Application\UseCases;

use App\Core\Telegram\Domain\Contracts\TelegramAdapterInterface;
use App\Modules\Pomodoro\Application\DTOs\GetPomodoroSettingsDTO;
use App\Modules\Pomodoro\Domain\Repository\PomodoroSettingsRepositoryInterface;
use App\Modules\User\Domain\Contracts\UserAdapterInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

final readonly class GetPomodoroSettingsUseCase
{
    public function __construct(
        private UserAdapterInterface $userAdapter,
        private PomodoroSettingsRepositoryInterface $pomodoroSettingsRepositoryInterface,
        private TelegramAdapterInterface $telegramAdapter
    ) {}

    public function execute(GetPomodoroSettingsDTO $data): void
    {
        try {
            try {
                $user = $this->userAdapter->getUserByTelegramId($data->telegramId);
            } catch (ModelNotFoundException $e) {
                $this->telegramAdapter->sendMessage(
                    chatId: $data->telegramId,
                    text: __('pomodoro.user_not_found')
                );

                return;
            }

            $settings = $this->pomodoroSettingsRepositoryInterface->getByUserId($user->id);

            if (! $settings) {
                $this->telegramAdapter->sendMessage(
                    chatId: $data->telegramId,
                    text: __('pomodoro.no_settings')
                );

                return;
            }

            $workDuration = $settings->work_duration;
            $breakDuration = $settings->break_duration;
            $repeatsCount = $settings->repeats_count;

            $message = __('pomodoro.settings_header')."\n\n";
            $message .= __('pomodoro.work_time', ['duration' => $workDuration])."\n";
            $message .= __('pomodoro.break_time', ['duration' => $breakDuration])."\n";
            $message .= __('pomodoro.repeats_count', ['count' => $repeatsCount])."\n";
            if (! is_null($settings->long_break_duration)) {
                $message .= __('pomodoro.long_break_duration', ['duration' => $settings->long_break_duration])."\n";
            }
            if (! is_null($settings->cycles_before_long_break)) {
                $message .= __('pomodoro.cycles_before_long_break', ['count' => $settings->cycles_before_long_break])." \n";
            }
            $message .= __('pomodoro.start_timer_hint');

            $this->telegramAdapter->sendMessage(
                chatId: $data->telegramId,
                text: $message
            );
        } catch (ModelNotFoundException $e) {
            $this->telegramAdapter->sendMessage(
                chatId: $data->telegramId,
                text: __('pomodoro.error_getting_data')
            );

            Log::error('Не удалось получить настройки пользователя: '.$data->telegramId.'. Ошибка - '.$e->getMessage());
        }
    }
}
