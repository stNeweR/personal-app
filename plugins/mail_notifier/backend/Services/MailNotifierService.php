<?php

declare(strict_types=1);

namespace Plugins\MailNotifier\Services;

use App\Modules\Pomodoro\Application\Events\PomodoroPhaseChangedEvent;
use App\Modules\Pomodoro\Domain\Repository\PomodoroSettingsRepositoryInterface;
use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Plugins\MailNotifier\Models\MailNotifierCredential;
use Plugins\MailNotifier\Models\MailNotifierHistory;

final class MailNotifierService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PomodoroSettingsRepositoryInterface $pomodoroSettingsRepository,
    ) {}

    public function handlePhaseChanged(PomodoroPhaseChangedEvent $event): void
    {
        Log::info('mail_notifier: handlePhaseChanged called', [
            'userId' => $event->userId,
            'oldStatus' => $event->oldStatus,
            'newStatus' => $event->newStatus,
        ]);

        if ($event->oldStatus === $event->newStatus) {
            Log::info('mail_notifier: skipped - same status');

            return;
        }

        $targets = ['work', 'break', 'long_break', 'finished'];
        if (! in_array($event->newStatus, $targets, true)) {
            Log::info('mail_notifier: skipped - newStatus not in targets', ['newStatus' => $event->newStatus]);

            return;
        }

        try {
            $user = $this->userRepository->getByUserId($event->userId);
        } catch (ModelNotFoundException) {
            Log::warning('mail_notifier: user not found', ['userId' => $event->userId]);

            return;
        }

        if ($user->email === null || $user->email === '' || $user->email_verified_at === null) {
            Log::info('mail_notifier: skipped - email not ready', [
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
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

        $subject = (string) __('pomodoro.notify_email_subject');

        Log::info('mail_notifier: sending email', [
            'to' => $user->email,
            'subject' => $subject,
        ]);

        $this->sendRaw($user->email, $subject, $message);
    }

    public function sendRaw(string $to, string $subject, string $body): bool
    {
        try {
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to);
                $message->subject($subject);
            });

            Log::info('Email sent via mail_notifier plugin', [
                'to' => $to,
                'subject' => $subject,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send email via mail_notifier plugin', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function isConnected(User $user): bool
    {
        return $user->email !== null && $user->email !== '';
    }

    public function isVerified(User $user): bool
    {
        return $user->email_verified_at !== null;
    }

    public function connect(User $user, string $email): void
    {
        $user->update([
            'email' => $email,
            'email_verified_at' => null,
        ]);
    }

    public function disconnect(User $user): void
    {
        $user->update([
            'email' => null,
            'email_verified_at' => null,
        ]);

        MailNotifierHistory::where('user_id', $user->id)->delete();
    }

    public function sendVerificationEmail(User $user): void
    {
        if ($user->email === null || $user->email === '') {
            throw new \RuntimeException('Email not set');
        }

        $code = strtoupper(Str::random(6));

        MailNotifierCredential::updateOrCreate(
            ['user_id' => $user->id],
            [
                'verification_token' => Crypt::encryptString($code),
                'token_expires_at' => now()->addHour(),
            ]
        );

        $this->sendRaw(
            $user->email,
            'Код подтверждения email',
            "Ваш код подтверждения: {$code}\n\nВведите этот код в приложении для подтверждения email.",
        );
    }

    public function verifyEmail(User $user, string $code): void
    {
        $credential = MailNotifierCredential::where('user_id', $user->id)->first();

        if ($credential === null) {
            throw new \RuntimeException('No verification pending');
        }

        if ($credential->token_expires_at !== null && $credential->token_expires_at->isPast()) {
            throw new \RuntimeException('Code expired');
        }

        $storedCode = Crypt::decryptString($credential->verification_token);

        if (! hash_equals(strtoupper($storedCode), strtoupper($code))) {
            throw new \RuntimeException('Invalid code');
        }

        $user->update([
            'email_verified_at' => now(),
        ]);

        $credential->delete();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getHistory(User $user): array
    {
        return MailNotifierHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function (MailNotifierHistory $history) {
                return [
                    'id' => $history->id,
                    'to' => $history->to,
                    'subject' => $history->subject,
                    'body' => $history->body,
                    'is_html' => $history->is_html,
                    'status' => $history->status,
                    'message_id' => $history->message_id,
                    'sent_at' => $history->created_at->toIso8601String(),
                ];
            })
            ->toArray();
    }
}
