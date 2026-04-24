<?php

namespace Tests\Feature\Pomodoro\Command;

use App\Modules\Pomodoro\Application\Jobs\ProcessPomodoroStageJob;
use App\Modules\Pomodoro\Domain\Enums\PomodoroStatusValue;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSession;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSettings;
use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Assertions\TelegramAssertion;
use Tests\SetUps\SetupTelegram;
use Tests\TestCase;

final class StartPomodoroTest extends TestCase
{
    use RefreshDatabase, SetupTelegram, TelegramAssertion;

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
                'text' => '/startpomodoro',
            ],
        ];
    }

    public function test_user_with_pomodoro_settings(): void
    {
        $data = $this->telegramMessageProvider();
        Queue::fake();

        $telegramId = $data['message']['from']['id'];

        $user = User::factory()->createOne([
            'telegram_id' => $telegramId,
        ]);

        PomodoroSettings::factory()->createOne([
            'user_id' => $user->id,
        ]);

        $this->postJson($this->telegramWebhookUrl, $data)
            ->assertOk();

        Queue::assertPushed(ProcessPomodoroStageJob::class);
        Queue::assertPushed(ProcessPomodoroStageJob::class, 1);
    }

    public function test_not_found_user(): void
    {
        $this->postJson($this->telegramWebhookUrl, $this->telegramMessageProvider())
            ->assertOk();

        $this->assertTelegramMessageContains(__('pomodoro.authorize_first'));
    }

    public function test_send_without_settings(): void
    {
        $data = $this->telegramMessageProvider();

        $telegramId = $data['message']['from']['id'];

        User::factory()->createOne([
            'telegram_id' => $telegramId,
        ]);

        $this->postJson($this->telegramWebhookUrl, $data)
            ->assertOk();

        $this->assertTelegramMessageContains(__('pomodoro.add_settings_first'));
    }
}
