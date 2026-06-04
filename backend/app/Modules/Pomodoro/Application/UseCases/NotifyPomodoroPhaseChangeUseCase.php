<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Application\UseCases;

use App\Core\MailNotifier\Domain\Contracts\MailNotifierApiClientInterface;
use App\Core\MailNotifier\Infrastructure\Services\MailNotifier\DTOs\SendEmailDTO;
use App\Core\Telegram\Domain\Contracts\TelegramApiClientInterface;
use App\Core\Telegram\Infrastructure\Services\Telegram\DTOs\SendMessageDTO;
use App\Modules\Pomodoro\Domain\Enums\PomodoroStatusValue;
use App\Modules\Pomodoro\Domain\Repository\PomodoroSettingsRepositoryInterface;
use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class NotifyPomodoroPhaseChangeUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TelegramApiClientInterface $telegramApiClient,
        private MailNotifierApiClientInterface $mailNotifierApiClient,
        private PomodoroSettingsRepositoryInterface $pomodoroSettingsRepository,
    ) {}

    public function execute(int $userId, PomodoroStatusValue $oldStatus, PomodoroStatusValue $newStatus): void
    {
        if ($oldStatus === $newStatus) {
            return;
        }

        $notifiable = [PomodoroStatusValue::WORK, PomodoroStatusValue::BREAK, PomodoroStatusValue::LONG_BREAK];
        if (! in_array($oldStatus, $notifiable, true)) {
            return;
        }

        $targets = [PomodoroStatusValue::WORK, PomodoroStatusValue::BREAK, PomodoroStatusValue::LONG_BREAK, PomodoroStatusValue::FINISHED];
        if (! in_array($newStatus, $targets, true)) {
            return;
        }

        try {
            $user = $this->userRepository->getByUserId($userId);
        } catch (ModelNotFoundException) {
            return;
        }

        $settings = $this->pomodoroSettingsRepository->getByUserId($userId);

        $message = match ($newStatus) {
            PomodoroStatusValue::BREAK => (string) __('pomodoro.notify_work_complete_short', [
                'duration' => $settings?->break_duration,
            ]),
            PomodoroStatusValue::LONG_BREAK => (string) __('pomodoro.notify_work_complete_long', [
                'duration' => $settings?->long_break_duration,
            ]),
            PomodoroStatusValue::WORK => (string) __('pomodoro.notify_break_complete', [
                'duration' => $settings?->work_duration,
            ]),
            PomodoroStatusValue::FINISHED => (string) __('pomodoro.notify_session_complete'),
        };

        match ($user->notification_channel) {
            'telegram' => $this->sendTelegram($user->telegram_id, $message),
            'email' => $this->sendEmail($user->email, $user->email_verified_at, $message),
            default => null,
        };
    }

    private function sendTelegram(?int $telegramId, string $message): void
    {
        if ($telegramId === null) {
            return;
        }

        $this->telegramApiClient->sendMessage(new SendMessageDTO(
            chatId: $telegramId,
            text: $message,
        ));
    }

    private function sendEmail(?string $email, mixed $emailVerifiedAt, string $message): void
    {
        if ($email === null || $email === '' || $emailVerifiedAt === null) {
            return;
        }

        $this->mailNotifierApiClient->sendEmail(new SendEmailDTO(
            to: $email,
            subject: (string) __('pomodoro.notify_email_subject'),
            body: $message,
            isHtml: false,
        ));
    }
}
