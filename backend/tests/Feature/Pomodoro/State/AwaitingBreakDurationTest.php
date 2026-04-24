<?php

declare(strict_types=1);

namespace Tests\Feature\Pomodoro\State;

use App\Modules\Pomodoro\Domain\Enums\StateValue;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSettings;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Models\UserState;
use Tests\Assertions\TelegramAssertion;
use Tests\SetUps\SetupTelegram;
use Tests\TestCase;

final class AwaitingBreakDurationTest extends TestCase
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
    private function telegramMessageProvider(string $message): array
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
                'text' => $message,
            ],
        ];
    }

    public function test_awaiting_break_duration_user_not_found(): void
    {
        $data = $this->telegramMessageProvider('5');
        $chatId = $data['message']['from']['id'];

        $this->postJson($this->telegramWebhookUrl, $data)->assertOk();

        $requests = $this->getTelegramRequests(self::SEND_MESSAGE_ENDPOINT);

        $this->assertCount(0, $requests);
    }

    public function test_awaiting_break_duration_success(): void
    {
        $data = $this->telegramMessageProvider('5');
        $chatId = $data['message']['from']['id'];

        $user = User::factory()->createOne([
            'telegram_id' => $chatId,
        ]);

        PomodoroSettings::factory()->createOne([
            'user_id' => $user->id,
            'work_duration' => 25,
            'break_duration' => 3,
            'repeats_count' => 4,
        ]);

        UserState::factory()->createOne([
            'user_id' => $user->id,
            'state_value' => StateValue::AWAITING_BREAK_DURATION->value,
        ]);

        $this->postJson($this->telegramWebhookUrl, $data)->assertOk();

        $requests = $this->getTelegramRequests(self::SEND_MESSAGE_ENDPOINT);

        $this->assertCount(1, $requests);
        $this->assertTelegramMessageSent($chatId, __('pomodoro.break_duration_saved'));

        $this->assertDatabaseHas(PomodoroSettings::class, [
            'user_id' => $user->id,
            'break_duration' => 5,
        ]);

        $this->assertDatabaseHas(UserState::class, [
            'user_id' => $user->id,
            'state_value' => StateValue::AWAITING_REPEATS_COUNT->value,
        ]);
    }
}
