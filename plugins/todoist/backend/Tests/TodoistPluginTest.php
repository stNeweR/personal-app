<?php

declare(strict_types=1);

namespace Tests\Plugin\Todoist;

use App\Modules\Plugin\Infrastructure\Models\Plugin;
use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class TodoistPluginTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseUrl = '/api/v1/todoist';

        Plugin::updateOrCreate(
            ['name' => 'todoist'],
            ['version' => '1.0.0', 'enabled' => true],
        );

        require_once base_path('plugins/todoist/backend/Plugin.php');

        $plugin = new \Plugins\Todoist\Plugin;
        $plugin->register();
        $plugin->boot();
    }

    public function test_guest_cannot_access_todoist_endpoints(): void
    {
        $this->getJson("{$this->baseUrl}/status")->assertUnauthorized();
        $this->postJson("{$this->baseUrl}/connect", ['api_token' => 'x'])->assertUnauthorized();
        $this->getJson("{$this->baseUrl}/tasks")->assertUnauthorized();
    }

    public function test_status_returns_disconnected_when_no_credentials(): void
    {
        $user = User::factory()->apiUser()->create();

        $response = $this->actingAs($user)->getJson("{$this->baseUrl}/status");

        $response->assertOk()
            ->assertJson(['connected' => false]);
    }

    public function test_user_can_connect_todoist(): void
    {
        $user = User::factory()->apiUser()->create();

        Http::fake([
            'https://api.todoist.com/api/v1/sync' => Http::response(['items' => []]),
        ]);

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

        Http::fake([
            'https://api.todoist.com/api/v1/sync' => Http::response('token rejected', 401),
        ]);

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/connect", [
            'api_token' => 'invalid-api-token',
        ]);

        $response->assertBadRequest();
        $this->assertDatabaseMissing('todoist_credentials', ['user_id' => $user->id]);
    }

    public function test_user_can_list_tasks(): void
    {
        $user = User::factory()->apiUser()->create();

        Http::fake([
            'https://api.todoist.com/api/v1/sync' => Http::response([
                'items' => [
                    [
                        'id' => '1',
                        'content' => 'First task',
                        'checked' => false,
                        'priority' => 4,
                        'due' => ['date' => now()->format('Y-m-d')],
                        'labels' => [],
                    ],
                    [
                        'id' => '2',
                        'content' => 'Second task',
                        'checked' => true,
                        'priority' => 1,
                        'due' => ['date' => now()->format('Y-m-d')],
                        'labels' => [],
                    ],
                ],
            ]),
        ]);

        $this->actingAs($user)->postJson("{$this->baseUrl}/connect", [
            'api_token' => 'valid-token',
        ])->assertOk();

        $response = $this->actingAs($user)->getJson("{$this->baseUrl}/tasks");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.content', 'First task')
            ->assertJsonPath('data.1.content', 'Second task');
    }

    public function test_user_can_create_task(): void
    {
        $user = User::factory()->apiUser()->create();

        Http::fake([
            'https://api.todoist.com/api/v1/sync' => Http::sequence()
                ->push(['items' => []])
                ->push([
                    'sync_status' => ['cmd-uuid' => 'ok'],
                    'temp_id_mapping' => ['temp-id' => '99'],
                ]),
        ]);

        $this->actingAs($user)->postJson("{$this->baseUrl}/connect", [
            'api_token' => 'valid-token',
        ])->assertOk();

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/tasks", [
            'content' => 'Buy milk',
            'priority' => 2,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.id', '99')
            ->assertJsonPath('data.content', 'Buy milk');
    }

    public function test_create_task_validates_content(): void
    {
        $user = User::factory()->apiUser()->create();

        Http::fake([
            'https://api.todoist.com/api/v1/sync' => Http::response(['items' => []]),
        ]);

        $this->actingAs($user)->postJson("{$this->baseUrl}/connect", [
            'api_token' => 'valid-token',
        ])->assertOk();

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/tasks", [
            'description' => 'no content',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    }

    public function test_user_can_complete_task(): void
    {
        $user = User::factory()->apiUser()->create();

        Http::fake([
            'https://api.todoist.com/api/v1/sync' => Http::sequence()
                ->push(['items' => []])
                ->push(['sync_status' => ['cmd-uuid' => 'ok']]),
        ]);

        $this->actingAs($user)->postJson("{$this->baseUrl}/connect", [
            'api_token' => 'valid-token',
        ])->assertOk();

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/tasks/42/complete");

        $response->assertOk()
            ->assertJsonPath('data.id', '42');
    }

    public function test_user_can_reopen_task(): void
    {
        $user = User::factory()->apiUser()->create();

        Http::fake([
            'https://api.todoist.com/api/v1/sync' => Http::sequence()
                ->push(['items' => []])
                ->push(['sync_status' => ['cmd-uuid' => 'ok']]),
        ]);

        $this->actingAs($user)->postJson("{$this->baseUrl}/connect", [
            'api_token' => 'valid-token',
        ])->assertOk();

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/tasks/42/reopen");

        $response->assertOk()
            ->assertJsonPath('data.id', '42');
    }

    public function test_user_can_delete_task(): void
    {
        $user = User::factory()->apiUser()->create();

        Http::fake([
            'https://api.todoist.com/api/v1/sync' => Http::sequence()
                ->push(['items' => []])
                ->push(['sync_status' => ['cmd-uuid' => 'ok']]),
        ]);

        $this->actingAs($user)->postJson("{$this->baseUrl}/connect", [
            'api_token' => 'valid-token',
        ])->assertOk();

        $response = $this->actingAs($user)->deleteJson("{$this->baseUrl}/tasks/42");

        $response->assertOk()
            ->assertJsonPath('data.id', '42');
    }

    public function test_user_can_disconnect_todoist(): void
    {
        $user = User::factory()->apiUser()->create();

        Http::fake([
            'https://api.todoist.com/api/v1/sync' => Http::response(['items' => []]),
        ]);

        $this->actingAs($user)->postJson("{$this->baseUrl}/connect", [
            'api_token' => 'valid-token',
        ])->assertOk();

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/disconnect");

        $response->assertOk();
        $this->assertDatabaseMissing('todoist_credentials', ['user_id' => $user->id]);
    }
}
