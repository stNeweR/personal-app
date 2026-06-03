<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Http\V1\Controllers;

use App\Modules\User\Infrastructure\Http\V1\Requests\Todoist\ConnectTodoistRequest;
use App\Modules\User\Infrastructure\Http\V1\Requests\Todoist\CreateTodoistTaskRequest;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Services\TodoistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TodoistController
{
    public function __construct(
        private readonly TodoistService $todoistService,
    ) {}

    public function status(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json([
            'connected' => $this->todoistService->isConnected($user),
        ]);
    }

    public function connect(ConnectTodoistRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $apiToken = (string) $request->string('api_token');

        try {
            $this->todoistService->connect($user, $apiToken);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => 'Todoist connected successfully']);
    }

    public function disconnect(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $this->todoistService->disconnect($user);

        return response()->json(['message' => 'Todoist disconnected']);
    }

    public function index(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $timezone = $request->query('timezone');

        try {
            $tasks = $this->todoistService->listTasks(
                $user,
                is_string($timezone) ? $timezone : null,
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['data' => $tasks]);
    }

    public function store(CreateTodoistTaskRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        /** @var array{content: string, description?: string, priority?: int} $validated */
        $validated = $request->validated();

        try {
            $task = $this->todoistService->createTask(
                $user,
                $validated['content'],
                $validated['description'] ?? null,
                $validated['priority'] ?? 1,
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['data' => $task], 201);
    }

    public function complete(string $id, Request $request): JsonResponse
    {
        return $this->runMutation($id, fn (User $user) => $this->todoistService->completeTask($user, $id), $request);
    }

    public function reopen(string $id, Request $request): JsonResponse
    {
        return $this->runMutation($id, fn (User $user) => $this->todoistService->reopenTask($user, $id), $request);
    }

    public function destroy(string $id, Request $request): JsonResponse
    {
        return $this->runMutation($id, fn (User $user) => $this->todoistService->deleteTask($user, $id), $request);
    }

    private function runMutation(string $id, callable $mutation, Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($id === '') {
            return response()->json(['message' => 'Task id is required'], 422);
        }

        try {
            $result = $mutation($user);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['data' => $result]);
    }
}
