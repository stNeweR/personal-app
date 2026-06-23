<?php

declare(strict_types=1);

namespace Tests\Plugin\Playlist;

use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Plugins\Playlist\Models\Playlist;
use Plugins\Playlist\Services\PlaylistService;
use Tests\TestCase;

final class PlaylistServiceTest extends TestCase
{
    use RefreshDatabase;

    private PlaylistService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Plugin classes are not in Composer autoload; register them manually.
        require_once base_path('plugins/playlist/backend/Plugin.php');

        $plugin = new \Plugins\Playlist\Plugin;
        $plugin->register();

        $this->service = new PlaylistService;
    }

    public function test_get_url_returns_null_when_no_playlist_exists(): void
    {
        $user = User::factory()->apiUser()->create();

        $this->assertNull($this->service->getUrl($user));
    }

    public function test_set_url_creates_playlist_record(): void
    {
        $user = User::factory()->apiUser()->create();

        $this->service->setUrl($user, 'https://example.com/playlist');

        $this->assertDatabaseHas('playlists', [
            'user_id' => $user->id,
            'url' => 'https://example.com/playlist',
        ]);
        $this->assertSame('https://example.com/playlist', $this->service->getUrl($user));
    }

    public function test_set_url_updates_existing_playlist_record(): void
    {
        $user = User::factory()->apiUser()->create();

        $this->service->setUrl($user, 'https://example.com/first');
        $this->service->setUrl($user, 'https://example.com/second');

        $this->assertSame(1, Playlist::where('user_id', $user->id)->count());
        $this->assertSame('https://example.com/second', $this->service->getUrl($user));
    }

    public function test_clear_url_removes_playlist_record(): void
    {
        $user = User::factory()->apiUser()->create();

        $this->service->setUrl($user, 'https://example.com/playlist');
        $this->service->clearUrl($user);

        $this->assertNull($this->service->getUrl($user));
        $this->assertDatabaseMissing('playlists', ['user_id' => $user->id]);
    }
}
