<?php

declare(strict_types=1);

namespace Tests\Feature\User\Todoist;

use App\Modules\Plugin\Domain\Contracts\PluginExecutorInterface;
use App\Modules\User\Infrastructure\Models\TodoistCredential;
use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

final class TodoistTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseUrl = '/api/v1/todoist';
    }

    public function test_guest_cannot_access_todoist_endpoints(): void
    {
        $this->getJson("{$this->baseUrl}/status")->assertUnauthorized();
        $this->getJson("{$this->baseUrl}/tasks")->assertUnauthorized();
        $this->postJson("{$this->baseUrl}/connect", ['api_token' => 'x'])->assertUnauthorized();
    }

    public function test_status_returns_disconnected_when_no_credentials(): void
    {
        $user = User::factory()->apiUser()->create();

        $response = $this->actingAs($user)->getJson("{$this->baseUrl}/status");

        $response->assertOk()
            ->assertJson(['connected' => false]);
    }

    public function test_status_returns_connected_when_credentials_exist(): void
    {
        $user = User::factory()->apiUser()->create();
        TodoistCredential::create([
            'user_id' => $user->id,
            'api_token' => Crypt::encryptString('some-token'),
        ]);

        $response = $this->actingAs($user)->getJson("{$this->baseUrl}/status");

        $response->assertOk()
            ->assertJson(['connected' => true]);
    }

    public function test_user_can_connect_todoist(): void
    {
        $user = User::factory()->apiUser()->create();

        $executor = $this->createMock(PluginExecutorInterface::class);
        $executor->expects($this->once())
            ->method('execute')
            ->with('todoist', 'list_tasks', ['token' => 'valid-api-token-12345'])
            ->willReturn([]);

        $this->app->instance(PluginExecutorInterface::class, $executor);

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/connect", [
            'api_token' => 'valid-api-token-12345',
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Todoist connected successfully']);

        $this->assertDatabaseHas('todoist_credentials', [
            'user_id' => $user->id,
        ]);
    }

    public function test_connect_validates_token_format(): void
    {
        $user = User::factory()->apiUser()->create();

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/connect", [
            'api_token' => 'short',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['api_token']);
    }

    public function test_connect_fails_when_token_is_invalid(): void
    {
        $user = User::factory()->apiUser()->create();

        $executor = $this->createMock(PluginExecutorInterface::class);
        $executor->method('execute')
            ->willThrowException(new \App\Modules\Plugin\Domain\Exceptions\PluginExecutionException('token rejected'));

        $this->app->instance(PluginExecutorInterface::class, $executor);

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/connect", [
            'api_token' => 'invalid-api-token',
        ]);

        $response->assertBadRequest();
        $this->assertDatabaseMissing('todoist_credentials', ['user_id' => $user->id]);
    }

    public function test_user_can_list_tasks(): void
    {
        $user = User::factory()->apiUser()->create();
        TodoistCredential::create([
            'user_id' => $user->id,
            'api_token' => Crypt::encryptString('stored-token'),
        ]);

        $executor = $this->createMock(PluginExecutorInterface::class);
        $executor->expects($this->once())
            ->method('execute')
            ->with('todoist', 'list_tasks', ['token' => 'stored-token'])
            ->willReturn([
                ['id' => '1', 'content' => 'First task', 'completed' => false],
                ['id' => '2', 'content' => 'Second task', 'completed' => true],
            ]);

        $this->app->instance(PluginExecutorInterface::class, $executor);

        $response = $this->actingAs($user)->getJson("{$this->baseUrl}/tasks");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    ['id' => '1', 'content' => 'First task'],
                    ['id' => '2', 'content' => 'Second task'],
                ],
            ]);
    }

    public function test_user_can_list_tasks_with_timezone(): void
    {
        $user = User::factory()->apiUser()->create();
        TodoistCredential::create([
            'user_id' => $user->id,
            'api_token' => Crypt::encryptString('stored-token'),
        ]);

        $executor = $this->createMock(PluginExecutorInterface::class);
        $executor->expects($this->once())
            ->method('execute')
            ->with('todoist', 'list_tasks', ['token' => 'stored-token', 'timezone' => 'Asia/Bangkok'])
            ->willReturn([]);

        $this->app->instance(PluginExecutorInterface::class, $executor);

        $response = $this->actingAs($user)->getJson("{$this->baseUrl}/tasks?timezone=Asia/Bangkok");

        $response->assertOk();
    }

    public function test_list_tasks_requires_connection(): void
    {
        $user = User::factory()->apiUser()->create();

        $response = $this->actingAs($user)->getJson("{$this->baseUrl}/tasks");

        $response->assertForbidden();
    }

    public function test_user_can_create_task(): void
    {
        $user = User::factory()->apiUser()->create();
        TodoistCredential::create([
            'user_id' => $user->id,
            'api_token' => Crypt::encryptString('stored-token'),
        ]);

        $executor = $this->createMock(PluginExecutorInterface::class);
        $executor->expects($this->once())
            ->method('execute')
            ->with('todoist', 'create_task', [
                'token' => 'stored-token',
                'content' => 'Buy milk',
                'description' => '',
                'priority' => 2,
            ])
            ->willReturn(['id' => '99', 'content' => 'Buy milk']);

        $this->app->instance(PluginExecutorInterface::class, $executor);

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/tasks", [
            'content' => 'Buy milk',
            'priority' => 2,
        ]);

        $response->assertCreated()
            ->assertJson([
                'data' => ['id' => '99', 'content' => 'Buy milk'],
            ]);
    }

    public function test_create_task_validates_content(): void
    {
        $user = User::factory()->apiUser()->create();
        TodoistCredential::create([
            'user_id' => $user->id,
            'api_token' => Crypt::encryptString('stored-token'),
        ]);

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/tasks", [
            'description' => 'no content',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    }

    public function test_create_task_validates_priority_range(): void
    {
        $user = User::factory()->apiUser()->create();
        TodoistCredential::create([
            'user_id' => $user->id,
            'api_token' => Crypt::encryptString('stored-token'),
        ]);

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/tasks", [
            'content' => 'Task',
            'priority' => 10,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['priority']);
    }

    public function test_user_can_complete_task(): void
    {
        $user = User::factory()->apiUser()->create();
        TodoistCredential::create([
            'user_id' => $user->id,
            'api_token' => Crypt::encryptString('stored-token'),
        ]);

        $executor = $this->createMock(PluginExecutorInterface::class);
        $executor->expects($this->once())
            ->method('execute')
            ->with('todoist', 'complete_task', ['token' => 'stored-token', 'id' => '42'])
            ->willReturn(['id' => '42', 'completed' => 'true']);

        $this->app->instance(PluginExecutorInterface::class, $executor);

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/tasks/42/complete");

        $response->assertOk()
            ->assertJson(['data' => ['id' => '42']]);
    }

    public function test_user_can_reopen_task(): void
    {
        $user = User::factory()->apiUser()->create();
        TodoistCredential::create([
            'user_id' => $user->id,
            'api_token' => Crypt::encryptString('stored-token'),
        ]);

        $executor = $this->createMock(PluginExecutorInterface::class);
        $executor->expects($this->once())
            ->method('execute')
            ->with('todoist', 'reopen_task', ['token' => 'stored-token', 'id' => '42'])
            ->willReturn(['id' => '42', 'reopened' => 'true']);

        $this->app->instance(PluginExecutorInterface::class, $executor);

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/tasks/42/reopen");

        $response->assertOk();
    }

    public function test_user_can_delete_task(): void
    {
        $user = User::factory()->apiUser()->create();
        TodoistCredential::create([
            'user_id' => $user->id,
            'api_token' => Crypt::encryptString('stored-token'),
        ]);

        $executor = $this->createMock(PluginExecutorInterface::class);
        $executor->expects($this->once())
            ->method('execute')
            ->with('todoist', 'delete_task', ['token' => 'stored-token', 'id' => '42'])
            ->willReturn(['id' => '42', 'deleted' => 'true']);

        $this->app->instance(PluginExecutorInterface::class, $executor);

        $response = $this->actingAs($user)->deleteJson("{$this->baseUrl}/tasks/42");

        $response->assertOk();
    }

    public function test_user_can_disconnect_todoist(): void
    {
        $user = User::factory()->apiUser()->create();
        TodoistCredential::create([
            'user_id' => $user->id,
            'api_token' => Crypt::encryptString('stored-token'),
        ]);

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/disconnect");

        $response->assertOk();
        $this->assertDatabaseMissing('todoist_credentials', ['user_id' => $user->id]);
    }
}
