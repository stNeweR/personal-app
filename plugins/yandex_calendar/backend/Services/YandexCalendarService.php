<?php

declare(strict_types=1);

namespace Plugins\YandexCalendar\Services;

use App\Modules\User\Infrastructure\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Plugins\YandexCalendar\Models\YandexCalendarCredential;

final class YandexCalendarService
{
    private const string CALDAV_BASE = 'https://caldav.yandex.ru';

    private const int TIMEOUT = 30;

    public function isConnected(User $user): bool
    {
        return YandexCalendarCredential::where('user_id', $user->id)->exists();
    }

    public function connect(User $user, string $email, string $appPassword): void
    {
        // Validate credentials by fetching events.
        $this->listEvents($email, $appPassword);

        YandexCalendarCredential::updateOrCreate(
            ['user_id' => $user->id],
            [
                'email' => $email,
                'app_password' => Crypt::encryptString($appPassword),
            ]
        );
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getTodayEvents(User $user, ?string $date = null): array
    {
        $credential = YandexCalendarCredential::where('user_id', $user->id)->first();

        if ($credential === null) {
            throw new \RuntimeException('Yandex Calendar not connected');
        }

        $appPassword = Crypt::decryptString($credential->app_password);

        return $this->listEvents($credential->email, $appPassword, $date);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function listEvents(string $email, string $appPassword, ?string $date = null): array
    {
        $today = $date !== null
            ? \Carbon\Carbon::parse($date)->startOfDay()
            : now()->startOfDay();

        $todayStr = $today->format('Ymd');

        // Fetch ±1 day to handle timezone offsets.
        $timeMin = $today->copy()->subDay()->format('Ymd') . 'T000000Z';
        $timeMax = $today->copy()->addDay()->format('Ymd') . 'T235959Z';

        $calendars = $this->discoverCalendars($email, $appPassword);

        if ($calendars === []) {
            throw new \RuntimeException('No calendars found for user');
        }

        $reportBody = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<C:calendar-query xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <D:prop>
    <C:calendar-data />
  </D:prop>
  <C:filter>
    <C:comp-filter name="VCALENDAR">
      <C:comp-filter name="VEVENT">
        <C:time-range start="{$timeMin}" end="{$timeMax}"/>
      </C:comp-filter>
    </C:comp-filter>
  </C:filter>
</C:calendar-query>
XML;

        $events = [];

        foreach ($calendars as $calendarUrl) {
            $calendarEvents = $this->queryCalendar($calendarUrl, $email, $appPassword, $reportBody);
            foreach ($calendarEvents as $event) {
                $events[$event['id']] = $event;
            }
        }

        return array_values(array_filter($events, function (array $event) use ($todayStr): bool {
            $startDate = substr($event['start'] ?? '', 0, 8);
            $endDate = substr($event['end'] ?? '', 0, 8);

            return $startDate === $todayStr || $endDate === $todayStr;
        }));
    }

    /**
     * @return list<string>
     */
    private function discoverCalendars(string $email, string $appPassword): array
    {
        $url = self::CALDAV_BASE . '/calendars/' . $email . '/';

        $propfindBody = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<D:propfind xmlns:D="DAV:">
  <D:prop>
    <D:resourcetype />
  </D:prop>
</D:propfind>
XML;

        $response = Http::withBasicAuth($email, $appPassword)
            ->withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8',
                'Depth' => '1',
            ])
            ->timeout(self::TIMEOUT)
            ->send('PROPFIND', $url, ['body' => $propfindBody]);

        if ($response->failed()) {
            throw new \RuntimeException('CalDAV discovery failed: ' . $response->body());
        }

        $xml = (string) $response->body();
        if ($xml === '') {
            return [];
        }

        $dom = new DOMDocument();
        if (@$dom->loadXML($xml) === false) {
            throw new \RuntimeException('Failed to parse CalDAV discovery response');
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('D', 'DAV:');
        $xpath->registerNamespace('C', 'urn:ietf:params:xml:ns:caldav');

        $calendars = [];
        $responses = $xpath->query('//D:response');

        foreach ($responses as $response) {
            $hrefNode = $xpath->query('D:href', $response)->item(0);
            $isCalendar = $xpath->query('.//C:calendar', $response)->length > 0;

            if ($isCalendar && $hrefNode !== null) {
                $href = $hrefNode->textContent;
                if ($href !== '') {
                    $calendars[] = self::CALDAV_BASE . $href;
                }
            }
        }

        return $calendars;
    }

    /**
     * @return list<array<string, string>>
     */
    private function queryCalendar(string $calendarUrl, string $email, string $appPassword, string $reportBody): array
    {
        $response = Http::withBasicAuth($email, $appPassword)
            ->withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8',
                'Depth' => '1',
            ])
            ->timeout(self::TIMEOUT)
            ->send('REPORT', $calendarUrl, ['body' => $reportBody]);

        if ($response->failed()) {
            // Skip failed calendars like the Go plugin does.
            return [];
        }

        $xml = (string) $response->body();
        if ($xml === '') {
            return [];
        }

        $dom = new DOMDocument();
        if (@$dom->loadXML($xml) === false) {
            return [];
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('D', 'DAV:');
        $xpath->registerNamespace('C', 'urn:ietf:params:xml:ns:caldav');

        $events = [];
        $dataNodes = $xpath->query('//C:calendar-data');

        foreach ($dataNodes as $node) {
            $ics = $node->textContent;
            if ($ics === '') {
                continue;
            }

            $event = $this->parseIcsEvent($ics);
            if ($event['id'] !== '') {
                $events[] = $event;
            }
        }

        return $events;
    }

    /**
     * @return array<string, string>
     */
    private function parseIcsEvent(string $ics): array
    {
        $event = [
            'id' => '',
            'summary' => '',
            'description' => '',
            'start' => '',
            'start_tz' => '',
            'end' => '',
            'end_tz' => '',
        ];

        $inEvent = false;
        $lines = preg_split('/\r\n|\r|\n/', $ics);

        if ($lines === false) {
            return $event;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === 'BEGIN:VEVENT') {
                $inEvent = true;
                continue;
            }

            if ($line === 'END:VEVENT') {
                break;
            }

            if (! $inEvent) {
                continue;
            }

            if (str_starts_with($line, 'SUMMARY:')) {
                $event['summary'] = substr($line, strlen('SUMMARY:'));
            } elseif (str_starts_with($line, 'UID:')) {
                $event['id'] = substr($line, strlen('UID:'));
            } elseif (str_starts_with($line, 'DESCRIPTION:')) {
                $event['description'] = substr($line, strlen('DESCRIPTION:'));
            } elseif (str_starts_with($line, 'DTSTART')) {
                $event['start'] = $this->extractIcsValue($line);
                $event['start_tz'] = $this->extractIcsTzid($line);
            } elseif (str_starts_with($line, 'DTEND')) {
                $event['end'] = $this->extractIcsValue($line);
                $event['end_tz'] = $this->extractIcsTzid($line);
            }
        }

        return $event;
    }

    private function extractIcsValue(string $line): string
    {
        $pos = strpos($line, ':');

        return $pos === false ? $line : substr($line, $pos + 1);
    }

    private function extractIcsTzid(string $line): string
    {
        $prefix = 'TZID=';
        $pos = strpos($line, $prefix);

        if ($pos === false) {
            return '';
        }

        $rest = substr($line, $pos + strlen($prefix));
        $end = strpos($rest, ':');

        return $end === false ? $rest : substr($rest, 0, $end);
    }
}
