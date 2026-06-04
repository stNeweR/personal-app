<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Http\V1\Controllers;

use App\Modules\User\Infrastructure\Http\V1\Requests\Playlist\SavePlaylistRequest;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Services\PlaylistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PlaylistController
{
    public function __construct(
        private readonly PlaylistService $playlistService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $url = $this->playlistService->getUrl($user);

        return response()->json(['data' => ['url' => $url]]);
    }

    public function save(SavePlaylistRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $url = (string) $request->string('url');

        try {
            $this->playlistService->setUrl($user, $url);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['data' => ['url' => $url]]);
    }

    public function destroy(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $this->playlistService->clearUrl($user);

        return response()->json(['data' => ['url' => null]]);
    }
}
