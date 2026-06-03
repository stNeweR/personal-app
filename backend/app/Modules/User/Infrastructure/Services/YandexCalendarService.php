<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Services;

use App\Modules\Plugin\Domain\Contracts\PluginExecutorInterface;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Models\YandexCalendarCredential;
use Illuminate\Support\Facades\Crypt;

final class YandexCalendarService
{
    public function __construct(
        private readonly PluginExecutorInterface $pluginExecutor,
    ) {}

    public function connect(User $user, string $email, string $appPassword): void
    {
        // Validate credentials by trying to fetch events
        $this->fetchEvents($email, $appPassword);

        YandexCalendarCredential::updateOrCreate(
            ['user_id' => $user->id],
            [
                'email' => $email,
                'app_password' => Crypt::encryptString($appPassword),
            ]
        );
    }

    /** @phpstan-ignore missingType.iterableValue */
    public function getTodayEvents(User $user, ?string $date = null): array
    {
        $credential = YandexCalendarCredential::where('user_id', $user->id)->first();

        if ($credential === null) {
            throw new \RuntimeException('Yandex Calendar not connected');
        }

        $appPassword = Crypt::decryptString($credential->app_password);

        return $this->fetchEvents($credential->email, $appPassword, $date);
    }

    /** @phpstan-ignore missingType.iterableValue */
    private function fetchEvents(string $email, string $appPassword, ?string $date = null): array
    {
        $today = $date !== null
            ? \Carbon\Carbon::parse($date)->startOfDay()
            : now()->startOfDay();

        $todayStr = $today->format('Ymd');

        // Fetch ±1 day to handle timezone offsets (events may be stored in different timezones)
        // CalDAV requires iCalendar datetime format: YYYYMMDDTHHMMSSZ
        $timeMin = $today->copy()->subDay()->format('Ymd').'T000000Z';
        $timeMax = $today->copy()->addDay()->format('Ymd').'T235959Z';

        /** @var array<int, array<string, string>> $events */
        $events = $this->pluginExecutor->execute('yandex_calendar', 'list_events', [
            'email' => $email,
            'app_password' => $appPassword,
            'time_min' => $timeMin,
            'time_max' => $timeMax,
        ]);

        // Filter to only events that occur on the target date
        return array_values(array_filter($events, function (array $event) use ($todayStr): bool {
            $startDate = substr($event['start'] ?? '', 0, 8);
            $endDate = substr($event['end'] ?? '', 0, 8);

            return $startDate === $todayStr || $endDate === $todayStr;
        }));
    }

    public function isConnected(User $user): bool
    {
        return YandexCalendarCredential::where('user_id', $user->id)->exists();
    }
}
