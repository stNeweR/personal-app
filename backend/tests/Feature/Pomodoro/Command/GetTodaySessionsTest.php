<?php

declare(strict_types=1);

namespace Tests\Feature\Pomodoro\Command;

use App\Modules\Pomodoro\Domain\Enums\PomodoroStatusValue;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSession;
use App\Modules\User\Infrastructure\Models\User;
use Tests\Assertions\TelegramAssertion;
use Tests\SetUps\SetupTelegram;
use Tests\TestCase;

final class GetTodaySessionsTest extends TestCase
{
    use SetupTelegram, TelegramAssertion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTelegramApi();
    }

    /**
     * @return array<string, mixed>
     */
    private function telegramMessageProvider(): array
    {
        return [
            'update_id' => 1001,
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => 123456789,
                    'is_bot' => false,
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'username' => 'testuser',
                    'language_code' => 'ru',
                ],
                'chat' => [
                    'id' => 123456789,
                    'type' => 'private',
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'username' => 'testuser',
                ],
                'date' => time(),
                'text' => '/getsessions',
            ],
        ];
    }

    public function test_get_today_sessions_user_not_found(): void
    {
        $data = $this->telegramMessageProvider();
        $chatId = $data['message']['from']['id'];

        $this->postJson($this->telegramWebhookUrl, $data)->assertOk();

        $requests = $this->getTelegramRequests(self::SEND_MESSAGE_ENDPOINT);

        $this->assertCount(1, $requests);
        $this->assertTelegramMessageSent($chatId, __('pomodoro.authorize_first'));
    }

    public function test_get_today_sessions_no_sessions(): void
    {
        $data = $this->telegramMessageProvider();
        $chatId = $data['message']['from']['id'];

        User::factory()->createOne([
            'telegram_id' => $chatId,
        ]);

        $this->postJson($this->telegramWebhookUrl, $data)->assertOk();

        $requests = $this->getTelegramRequests(self::SEND_MESSAGE_ENDPOINT);

        $this->assertCount(1, $requests);
        $this->assertTelegramMessageSent($chatId, __('pomodoro.no_sessions_today'));
    }

    public function test_get_today_sessions_with_sessions(): void
    {
        $data = $this->telegramMessageProvider();
        $chatId = $data['message']['from']['id'];

        $user = User::factory()->createOne([
            'telegram_id' => $chatId,
        ]);

        PomodoroSession::create([
            'user_id' => $user->id,
            'current_status' => PomodoroStatusValue::WORK,
            'start_at' => now(),
            'end_at' => null,
            'current_cycle' => 1,
        ]);

        $this->postJson($this->telegramWebhookUrl, $data)->assertOk();

        $requests = $this->getTelegramRequests(self::SEND_MESSAGE_ENDPOINT);

        $this->assertCount(1, $requests);
        $this->assertTelegramMessageContains(__('pomodoro.sessions_header'));
        $this->assertTelegramMessageContains(__('pomodoro.table_header_num'));
        $this->assertTelegramMessageContains(__('pomodoro.table_header_start_time'));
        $this->assertTelegramMessageContains(__('pomodoro.table_header_status'));
        $this->assertTelegramMessageContains(__('pomodoro.table_header_cycle'));
    }

    public function test_get_today_sessions_multiple_sessions(): void
    {
        $data = $this->telegramMessageProvider();
        $chatId = $data['message']['from']['id'];

        $user = User::factory()->createOne([
            'telegram_id' => $chatId,
        ]);

        PomodoroSession::create([
            'user_id' => $user->id,
            'current_status' => PomodoroStatusValue::WORK,
            'start_at' => now()->subHours(2),
            'end_at' => now()->subHours(1),
            'current_cycle' => 1,
        ]);

        PomodoroSession::create([
            'user_id' => $user->id,
            'current_status' => PomodoroStatusValue::BREAK,
            'start_at' => now()->subMinutes(30),
            'end_at' => null,
            'current_cycle' => 2,
        ]);

        $this->postJson($this->telegramWebhookUrl, $data)->assertOk();

        $requests = $this->getTelegramRequests(self::SEND_MESSAGE_ENDPOINT);

        $this->assertCount(1, $requests);
        $this->assertTelegramMessageContains(__('pomodoro.sessions_header'));
    }

    public function test_get_today_sessions_with_all_statuses(): void
    {
        $data = $this->telegramMessageProvider();
        $chatId = $data['message']['from']['id'];

        $user = User::factory()->createOne([
            'telegram_id' => $chatId,
        ]);

        $statuses = [
            PomodoroStatusValue::WORK,
            PomodoroStatusValue::BREAK,
            PomodoroStatusValue::LONG_BREAK,
            PomodoroStatusValue::PAUSED,
            PomodoroStatusValue::FINISHED,
        ];

        foreach ($statuses as $index => $status) {
            PomodoroSession::create([
                'user_id' => $user->id,
                'current_status' => $status,
                'start_at' => now()->subMinutes($index * 10),
                'end_at' => $status === PomodoroStatusValue::FINISHED ? now() : null,
                'current_cycle' => $index + 1,
            ]);
        }

        $this->postJson($this->telegramWebhookUrl, $data)->assertOk();

        $requests = $this->getTelegramRequests(self::SEND_MESSAGE_ENDPOINT);

        $this->assertCount(1, $requests);
        $this->assertTelegramMessageContains(__('pomodoro.sessions_header'));
        $this->assertTelegramMessageContains(__('pomodoro.status_work'));
        $this->assertTelegramMessageContains(__('pomodoro.status_break'));
        $this->assertTelegramMessageContains(__('pomodoro.status_long_break'));
        $this->assertTelegramMessageContains(__('pomodoro.status_paused'));
        $this->assertTelegramMessageContains(__('pomodoro.status_finished'));
    }
}
