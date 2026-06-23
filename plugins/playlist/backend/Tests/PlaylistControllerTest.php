<?php

declare(strict_types=1);

namespace Tests\Plugin\Playlist;

use App\Modules\Plugin\Infrastructure\Models\Plugin;
use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PlaylistControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseUrl = '/api/v1/playlist';

        // Mark the plugin as enabled so the provider logic is mirrored in tests.
        Plugin::updateOrCreate(
            ['name' => 'playlist'],
            ['version' => '1.0.0', 'enabled' => true],
        );

        // Plugin classes are not in Composer autoload; register and boot manually.
        require_once base_path('plugins/playlist/backend/Plugin.php');

        $plugin = new \Plugins\Playlist\Plugin;
        $plugin->register();
        $plugin->boot();
    }

    public function test_guest_cannot_access_playlist_endpoints(): void
    {
        $this->getJson($this->baseUrl)->assertUnauthorized();
        $this->postJson($this->baseUrl, ['url' => 'https://example.com'])->assertUnauthorized();
        $this->deleteJson($this->baseUrl)->assertUnauthorized();
    }

    public function test_authenticated_user_can_show_empty_playlist(): void
    {
        $user = User::factory()->apiUser()->create();

        $response = $this->actingAs($user)->getJson($this->baseUrl);

        $response->assertOk()
            ->assertJson(['data' => ['url' => null]]);
    }

    public function test_authenticated_user_can_save_playlist_url(): void
    {
        $user = User::factory()->apiUser()->create();

        $response = $this->actingAs($user)->postJson($this->baseUrl, [
            'url' => 'https://example.com/playlist',
        ]);

        $response->assertOk()
            ->assertJson(['data' => ['url' => 'https://example.com/playlist']]);

        $this->assertDatabaseHas('playlists', [
            'user_id' => $user->id,
            'url' => 'https://example.com/playlist',
        ]);
    }

    public function test_save_validates_url_format(): void
    {
        $user = User::factory()->apiUser()->create();

        $response = $this->actingAs($user)->postJson($this->baseUrl, [
            'url' => 'not-a-valid-url',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);
    }

    public function test_show_returns_saved_url(): void
    {
        $user = User::factory()->apiUser()->create();

        $this->actingAs($user)
            ->postJson($this->baseUrl, ['url' => 'https://example.com/playlist'])
            ->assertOk();

        $response = $this->actingAs($user)->getJson($this->baseUrl);

        $response->assertOk()
            ->assertJson(['data' => ['url' => 'https://example.com/playlist']]);
    }

    public function test_authenticated_user_can_delete_playlist_url(): void
    {
        $user = User::factory()->apiUser()->create();

        $this->actingAs($user)
            ->postJson($this->baseUrl, ['url' => 'https://example.com/playlist'])
            ->assertOk();

        $response = $this->actingAs($user)->deleteJson($this->baseUrl);

        $response->assertOk()
            ->assertJson(['data' => ['url' => null]]);

        $this->assertDatabaseMissing('playlists', ['user_id' => $user->id]);
    }
}
