<?php

declare(strict_types=1);

namespace Plugins\Playlist\Services;

use Plugins\Playlist\Models\Playlist;
use App\Modules\User\Infrastructure\Models\User;

final class PlaylistService
{
    public function getUrl(User $user): ?string
    {
        $playlist = Playlist::where('user_id', $user->id)->first();

        return $playlist?->url;
    }

    public function setUrl(User $user, string $url): void
    {
        Playlist::updateOrCreate(
            ['user_id' => $user->id],
            ['url' => $url],
        );
    }

    public function clearUrl(User $user): void
    {
        Playlist::where('user_id', $user->id)->delete();
    }
}
