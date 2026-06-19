<?php

declare(strict_types=1);

namespace Tests\Plugin\YandexCalendar;

use App\Modules\Plugin\Infrastructure\Models\Plugin;
use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class YandexCalendarPluginTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseUrl = '/api/v1/yandex-calendar';

        Plugin::updateOrCreate(
            ['name' => 'yandex_calendar'],
            ['version' => '1.0.0', 'enabled' => true],
        );

        require_once base_path('plugins/yandex_calendar/backend/Plugin.php');

        $plugin = new \Plugins\YandexCalendar\Plugin();
        $plugin->register();
        $plugin->boot();
    }

    public function test_guest_cannot_access_yandex_calendar_endpoints(): void
    {
        $this->getJson("{$this->baseUrl}/status")->assertUnauthorized();
        $this->postJson("{$this->baseUrl}/connect", ['email' => 'a@b.c', 'app_password' => 'x'])->assertUnauthorized();
        $this->getJson("{$this->baseUrl}/today")->assertUnauthorized();
    }

    public function test_status_returns_disconnected_when_no_credentials(): void
    {
        $user = User::factory()->apiUser()->create();

        $response = $this->actingAs($user)->getJson("{$this->baseUrl}/status");

        $response->assertOk()
            ->assertJson(['connected' => false]);
    }

    public function test_connect_validates_input(): void
    {
        $user = User::factory()->apiUser()->create();

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/connect", [
            'email' => 'not-an-email',
            'app_password' => '',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'app_password']);
    }

    public function test_user_can_connect_yandex_calendar(): void
    {
        $user = User::factory()->apiUser()->create();

        Http::fake([
            'https://caldav.yandex.ru/*' => Http::sequence()
                ->push($this->discoveryResponse(), 207)
                ->push($this->emptyReportResponse(), 207),
        ]);

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/connect", [
            'email' => 'user@yandex.ru',
            'app_password' => 'app-password',
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Yandex Calendar connected successfully']);

        $this->assertDatabaseHas('yandex_calendar_credentials', [
            'user_id' => $user->id,
            'email' => 'user@yandex.ru',
        ]);
    }

    public function test_user_can_list_today_events(): void
    {
        $user = User::factory()->apiUser()->create();

        Http::fake([
            'https://caldav.yandex.ru/*' => Http::sequence()
                ->push($this->discoveryResponse(), 207)
                ->push($this->eventReportResponse(), 207),
        ]);

        $this->actingAs($user)->postJson("{$this->baseUrl}/connect", [
            'email' => 'user@yandex.ru',
            'app_password' => 'app-password',
        ])->assertOk();

        $response = $this->actingAs($user)->getJson("{$this->baseUrl}/today?date=" . now()->format('Y-m-d'));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.summary', 'Meeting');
    }

    private function discoveryResponse(): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<D:multistatus xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <D:response>
    <D:href>/calendars/user@yandex.ru/events/</D:href>
    <D:propstat>
      <D:prop>
        <D:resourcetype>
          <C:calendar />
        </D:resourcetype>
      </D:prop>
    </D:propstat>
  </D:response>
</D:multistatus>
XML;
    }

    private function emptyReportResponse(): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<C:multistatus xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
</C:multistatus>
XML;
    }

    private function eventReportResponse(): string
    {
        $today = now()->format('Ymd');

        $ics = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:event-1
SUMMARY:Meeting
DESCRIPTION:Team sync
DTSTART;TZID=Europe/Moscow:{$today}T110000
DTEND;TZID=Europe/Moscow:{$today}T120000
END:VEVENT
END:VCALENDAR
ICS;

        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<D:multistatus xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <D:response>
    <D:propstat>
      <D:prop>
        <C:calendar-data>{$ics}</C:calendar-data>
      </D:prop>
    </D:propstat>
  </D:response>
</D:multistatus>
XML;
    }
}
