<?php

declare(strict_types=1);

namespace Plugins\Telegram\Http\Controllers;

use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\Telegram\Services\TelegramNotifierService;

final class TelegramNotifierController
{
    public function __construct(
        private readonly TelegramNotifierService $telegramNotifierService,
    ) {}

    public function status(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json([
            'connected' => $this->telegramNotifierService->isConnected($user),
            'chat_id' => $user->telegram_id,
            'bot_name' => config('telegram.telegram_bot_name'),
        ]);
    }

    public function disconnect(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $this->telegramNotifierService->disconnect($user);

        return response()->json(['message' => 'Telegram disconnected']);
    }

    public function history(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json($this->telegramNotifierService->getHistory($user));
    }
}
