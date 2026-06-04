<?php

declare(strict_types=1);

namespace Tests\Feature\Plugin;

use App\Modules\Plugin\Domain\Contracts\PluginExecutorInterface;
use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ExecutePluginTest extends TestCase
{
    use RefreshDatabase;

    private string $url;

    protected function setUp(): void
    {
        parent::setUp();
        $this->url = '/api/v1/plugins';
    }

    public function test_guest_cannot_execute_plugin(): void
    {
        $response = $this->postJson("{$this->url}/yandex_calendar/list_events");

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_execute_plugin(): void
    {
        $user = User::create([
            'telegram_id' => fake()->randomNumber(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ]);

        $mock = $this->createMock(PluginExecutorInterface::class);
        $mock->method('execute')
            ->with('yandex_calendar', 'list_events', [])
            ->willReturn(['events' => []]);

        $this->app->instance(PluginExecutorInterface::class, $mock);

        $response = $this->actingAs($user)->postJson("{$this->url}/yandex_calendar/list_events");

        $response->assertOk()
            ->assertJson([
                'data' => ['events' => []],
            ]);
    }

    public function test_plugin_with_input_payload(): void
    {
        $user = User::create([
            'telegram_id' => fake()->randomNumber(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ]);

        $mock = $this->createMock(PluginExecutorInterface::class);
        $mock->method('execute')
            ->with('converter', 'currency', ['amount' => 100, 'from' => 'USD', 'to' => 'EUR'])
            ->willReturn(['amount' => 92.0, 'from' => 'USD', 'to' => 'EUR', 'original' => 100]);

        $this->app->instance(PluginExecutorInterface::class, $mock);

        $response = $this->actingAs($user)->postJson("{$this->url}/converter/currency", [
            'input' => [
                'amount' => 100,
                'from' => 'USD',
                'to' => 'EUR',
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'amount' => 92.0,
                    'from' => 'USD',
                    'to' => 'EUR',
                    'original' => 100,
                ],
            ]);
    }

    public function test_plugin_execution_error_returns_bad_request(): void
    {
        $user = User::create([
            'telegram_id' => fake()->randomNumber(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ]);

        $mock = $this->createMock(PluginExecutorInterface::class);
        $mock->method('execute')
            ->willThrowException(new \App\Modules\Plugin\Domain\Exceptions\PluginExecutionException('unknown action'));

        $this->app->instance(PluginExecutorInterface::class, $mock);

        $response = $this->actingAs($user)->postJson("{$this->url}/todoist/unknown_action");

        $response->assertBadRequest();
    }
}
