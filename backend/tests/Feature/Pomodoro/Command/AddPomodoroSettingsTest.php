<?php

declare(strict_types=1);

namespace Tests\Feature\Pomodoro\Command;

use App\Modules\Pomodoro\Domain\Enums\StateValue;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Models\UserState;
use Tests\Assertions\TelegramAssertion;
use Tests\SetUps\SetupTelegram;
use Tests\TestCase;

final class AddPomodoroSettingsTest extends TestCase
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
                'text' => '/addpomosettings',
            ],
        ];
    }

    public function test_send_command(): void
    {
        $data = $this->telegramMessageProvider();
        $chatId = $data['message']['from']['id'];

        User::factory()->createOne([
            'telegram_id' => $chatId,
        ]);

        $this->postJson($this->telegramWebhookUrl, $data)->assertOk();

        $requests = $this->getTelegramRequests(self::SEND_MESSAGE_ENDPOINT);

        $this->assertCount(1, $requests);
        $this->assertTelegramMessageSent(
            $chatId,
            __('pomodoro.enter_work_duration'),
        );
    }

    public function test_send_command_when_user_not_found(): void
    {
        $data = $this->telegramMessageProvider();
        $chatId = $data['message']['from']['id'];

        $this->postJson($this->telegramWebhookUrl, $data)->assertOk();

        $requests = $this->getTelegramRequests(self::SEND_MESSAGE_ENDPOINT);

        $this->assertCount(1, $requests);
        $this->assertTelegramMessageSent($chatId, __('pomodoro.try_later'));
    }

    public function test_send_command_updates_user_state(): void
    {
        $data = $this->telegramMessageProvider();
        $chatId = $data['message']['from']['id'];

        $user = User::factory()->createOne([
            'telegram_id' => $chatId,
        ]);

        $this->postJson($this->telegramWebhookUrl, $data)->assertOk();

        $this->assertDatabaseHas(UserState::class, [
            'id' => $user->id,
            'state_value' => StateValue::AWAITING_WORK_DURATION->value,
        ]);
    }
}
