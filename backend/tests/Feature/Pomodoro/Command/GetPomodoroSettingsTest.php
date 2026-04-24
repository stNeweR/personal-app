<?php

declare(strict_types=1);

namespace Tests\Feature\Pomodoro\Command;

use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSettings;
use App\Modules\User\Infrastructure\Models\User;
use Tests\Assertions\TelegramAssertion;
use Tests\SetUps\SetupTelegram;
use Tests\TestCase;

final class GetPomodoroSettingsTest extends TestCase
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
                'text' => '/getpomosettings',
            ],
        ];
    }

    public function test_get_settings_when_user_not_found(): void
    {
        $data = $this->telegramMessageProvider();
        $chatId = $data['message']['from']['id'];

        $this->postJson($this->telegramWebhookUrl, $data)->assertOk();

        $requests = $this->getTelegramRequests(self::SEND_MESSAGE_ENDPOINT);

        $this->assertCount(1, $requests);
        $this->assertTelegramMessageSent($chatId, __('pomodoro.user_not_found'));
    }

    public function test_get_settings_when_settings_not_found(): void
    {
        $data = $this->telegramMessageProvider();
        $chatId = $data['message']['from']['id'];

        User::factory()->createOne([
            'telegram_id' => $chatId,
        ]);

        $this->postJson($this->telegramWebhookUrl, $data)->assertOk();

        $requests = $this->getTelegramRequests(self::SEND_MESSAGE_ENDPOINT);

        $this->assertCount(1, $requests);
        $this->assertTelegramMessageSent($chatId, __('pomodoro.no_settings'));
    }

    public function test_get_settings_success(): void
    {
        $data = $this->telegramMessageProvider();
        $chatId = $data['message']['from']['id'];

        $user = User::factory()->createOne([
            'telegram_id' => $chatId,
        ]);

        PomodoroSettings::factory()->createOne([
            'user_id' => $user->id,
            'work_duration' => 25,
            'break_duration' => 5,
            'repeats_count' => 4,
            'long_break_duration' => 15,
            'cycles_before_long_break' => 3,
        ]);

        $this->postJson($this->telegramWebhookUrl, $data)->assertOk();

        $requests = $this->getTelegramRequests(self::SEND_MESSAGE_ENDPOINT);

        $this->assertCount(1, $requests);
        $this->assertTelegramMessageContains(__('pomodoro.settings_header'));
        $this->assertTelegramMessageContains(__('pomodoro.work_time', ['duration' => 25]));
        $this->assertTelegramMessageContains(__('pomodoro.break_time', ['duration' => 5]));
        $this->assertTelegramMessageContains(__('pomodoro.repeats_count', ['count' => 4]));
        $this->assertTelegramMessageContains(__('pomodoro.long_break_duration', ['duration' => 15]));
        $this->assertTelegramMessageContains(__('pomodoro.cycles_before_long_break', ['count' => 3]));
        $this->assertTelegramMessageContains(__('pomodoro.start_timer_hint'));
    }

    public function test_get_settings_without_optional_fields(): void
    {
        $data = $this->telegramMessageProvider();
        $chatId = $data['message']['from']['id'];

        $user = User::factory()->createOne([
            'telegram_id' => $chatId,
        ]);

        PomodoroSettings::factory()->createOne([
            'user_id' => $user->id,
            'work_duration' => 30,
            'break_duration' => 10,
            'repeats_count' => 5,
            'long_break_duration' => null,
            'cycles_before_long_break' => null,
        ]);

        $this->postJson($this->telegramWebhookUrl, $data)->assertOk();

        $requests = $this->getTelegramRequests(self::SEND_MESSAGE_ENDPOINT);

        $this->assertCount(1, $requests);
        $this->assertTelegramMessageContains(__('pomodoro.settings_header'));
        $this->assertTelegramMessageContains(__('pomodoro.work_time', ['duration' => 30]));
        $this->assertTelegramMessageContains(__('pomodoro.break_time', ['duration' => 10]));
        $this->assertTelegramMessageContains(__('pomodoro.repeats_count', ['count' => 5]));
    }
}
