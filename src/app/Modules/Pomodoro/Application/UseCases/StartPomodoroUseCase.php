<?php

namespace App\Modules\Pomodoro\Application\UseCases;

use App\Core\Telegram\Domain\Contracts\TelegramApiClientInterface;
use App\Core\Telegram\Infrastructure\Services\Telegram\DTOs\SendMessageDTO;
use App\Modules\Pomodoro\Application\DTOs\StartPomodoroDTO;
use App\Modules\Pomodoro\Application\Jobs\ProcessPomodoroStageJob;
use App\Modules\Pomodoro\Domain\Enums\PomodoroStatusValue;
use App\Modules\Pomodoro\Domain\Repository\PomodoroSessionsRepositoryInterface;
use App\Modules\Pomodoro\Domain\Repository\PomodoroSettingsRepositoryInterface;
use App\Modules\User\Domain\Contracts\UserAdapterInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class StartPomodoroUseCase
{
    public function __construct(
        private PomodoroSessionsRepositoryInterface $pomodoroSessionsRepository,
        private UserAdapterInterface $userAdapter,
        private TelegramApiClientInterface $telegramApiClient,
        private PomodoroSettingsRepositoryInterface $pomodoroSettingsRepository,
    ) {}

    public function execute(StartPomodoroDTO $data): void
    {
        try {
            $user = $this->userAdapter->getUserByTelegramId($data->telegramId);
        } catch (ModelNotFoundException) {
            $this->telegramApiClient->sendMessage(new SendMessageDTO(
                $data->telegramId,
                __('pomodoro.authorize_first')
            ));

            return;
        }

        $settings = $this->pomodoroSettingsRepository->getByUserId($user->id);
        if (is_null($settings)) {
            $this->telegramApiClient->sendMessage(new SendMessageDTO(
                $data->telegramId,
                __('pomodoro.add_settings_first')
            ));

            return;
        }

        $activeSession = $this->pomodoroSessionsRepository->findActiveSession($user->id);

        if ($activeSession) {
            $this->telegramApiClient->sendMessage(new SendMessageDTO(
                $data->telegramId,
                __('pomodoro.active_session_exists')
            ));

            return;
        }

        $session = $this->pomodoroSessionsRepository->create($user->id);

        ProcessPomodoroStageJob::dispatch($session->id, 1, PomodoroStatusValue::WORK);
    }
}
