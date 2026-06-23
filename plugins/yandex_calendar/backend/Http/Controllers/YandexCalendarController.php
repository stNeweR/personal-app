<?php

declare(strict_types=1);

namespace Plugins\YandexCalendar\Http\Controllers;

use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\YandexCalendar\Http\Requests\ConnectYandexCalendarRequest;
use Plugins\YandexCalendar\Services\YandexCalendarService;

final class YandexCalendarController
{
    public function __construct(
        private readonly YandexCalendarService $calendarService,
    ) {}

    public function connect(ConnectYandexCalendarRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        /** @var array{email: string, app_password: string} $validated */
        $validated = $request->validated();

        try {
            $this->calendarService->connect($user, $validated['email'], $validated['app_password']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => 'Yandex Calendar connected successfully']);
    }

    public function today(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $date = $request->query('date');

        try {
            $events = $this->calendarService->getTodayEvents($user, is_string($date) ? $date : null);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['data' => $events]);
    }

    public function status(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json([
            'connected' => $this->calendarService->isConnected($user),
        ]);
    }
}
