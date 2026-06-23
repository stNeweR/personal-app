<?php

declare(strict_types=1);

namespace Plugins\Telegram\Services;

use App\Core\Telegram\Domain\Contracts\TelegramAdapterInterface;
use App\Modules\Pomodoro\Application\Events\PomodoroPhaseChangedEvent;
use App\Modules\Pomodoro\Domain\Repository\PomodoroSettingsRepositoryInterface;
use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Plugins\Telegram\Models\TelegramNotifierHistory;

final class TelegramNotifierService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PomodoroSettingsRepositoryInterface $pomodoroSettingsRepository,
        private TelegramAdapterInterface $telegramAdapter,
    ) {}

    public function handlePhaseChanged(PomodoroPhaseChangedEvent $event): void
    {
        Log::info('telegram_notifier: handlePhaseChanged called', [
            'userId' => $event->userId,
            'oldStatus' => $event->oldStatus,
            'newStatus' => $event->newStatus,
        ]);

        if ($event->oldStatus === $event->newStatus) {
            Log::info('telegram_notifier: skipped - same status');

            return;
        }

        $targets = ['work', 'break', 'long_break', 'finished'];
        if (! in_array($event->newStatus, $targets, true)) {
            Log::info('telegram_notifier: skipped - newStatus not in targets', ['newStatus' => $event->newStatus]);

            return;
        }

        try {
            $user = $this->userRepository->getByUserId($event->userId);
        } catch (ModelNotFoundException) {
            Log::warning('telegram_notifier: user not found', ['userId' => $event->userId]);

            return;
        }

        if ($user->notification_channel !== 'telegram' || $user->telegram_id === null) {
            Log::info('telegram_notifier: skipped - telegram not configured', [
                'notification_channel' => $user->notification_channel,
                'telegram_id' => $user->telegram_id,
            ]);

            return;
        }

        $settings = $this->pomodoroSettingsRepository->getByUserId($event->userId);

        $message = match ($event->newStatus) {
            'break' => (string) __('pomodoro.notify_work_complete_short', [
                'duration' => $settings?->break_duration,
            ]),
            'long_break' => (string) __('pomodoro.notify_work_complete_long', [
                'duration' => $settings?->long_break_duration,
            ]),
            'work' => (string) __('pomodoro.notify_break_complete', [
                'duration' => $settings?->work_duration,
            ]),
            'finished' => (string) __('pomodoro.notify_session_complete'),
            default => '',
        };

        if ($message === '') {
            return;
        }

        Log::info('telegram_notifier: sending message', [
            'chat_id' => $user->telegram_id,
        ]);

        try {
            $this->telegramAdapter->sendMessage(
                (int) $user->telegram_id,
                $message,
                'HTML'
            );

            TelegramNotifierHistory::create([
                'user_id' => $user->id,
                'chat_id' => $user->telegram_id,
                'message' => $message,
                'status' => 'sent',
            ]);
        } catch (\Throwable $e) {
            Log::error('telegram_notifier: failed to send message', [
                'chat_id' => $user->telegram_id,
                'error' => $e->getMessage(),
            ]);

            TelegramNotifierHistory::create([
                'user_id' => $user->id,
                'chat_id' => $user->telegram_id,
                'message' => $message,
                'status' => 'failed',
            ]);
        }
    }

    public function isConnected(User $user): bool
    {
        return $user->telegram_id !== null;
    }

    public function disconnect(User $user): void
    {
        if ($user->notification_channel === 'telegram') {
            $user->update(['notification_channel' => null]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getHistory(User $user): array
    {
        return TelegramNotifierHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function (TelegramNotifierHistory $history) {
                return [
                    'id' => $history->id,
                    'chat_id' => $history->chat_id,
                    'message' => $history->message,
                    'status' => $history->status,
                    'message_id' => $history->message_id,
                    'sent_at' => $history->created_at->toIso8601String(),
                ];
            })
            ->toArray();
    }
}
