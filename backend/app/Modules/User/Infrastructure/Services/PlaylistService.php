<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Services;

use App\Modules\Plugin\Domain\Contracts\PluginExecutorInterface;
use App\Modules\User\Infrastructure\Models\Playlist;
use App\Modules\User\Infrastructure\Models\User;

final class PlaylistService
{
    public function __construct(
        private readonly PluginExecutorInterface $pluginExecutor,
    ) {}

    public function getUrl(User $user): ?string
    {
        $playlist = Playlist::where('user_id', $user->id)->first();

        return $playlist?->url;
    }

    public function setUrl(User $user, string $url): void
    {
        $this->pluginExecutor->execute('playlist', 'set_url', [
            'user_id' => $user->id,
            'url' => $url,
        ]);

        Playlist::updateOrCreate(
            ['user_id' => $user->id],
            ['url' => $url],
        );
    }

    public function clearUrl(User $user): void
    {
        $this->pluginExecutor->execute('playlist', 'delete_url', [
            'user_id' => $user->id,
        ]);

        Playlist::where('user_id', $user->id)->delete();
    }
}
