<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Services;

use App\Modules\Plugin\Domain\Contracts\PluginExecutorInterface;
use App\Modules\User\Infrastructure\Models\TodoistCredential;
use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Support\Facades\Crypt;

final class TodoistService
{
    public function __construct(
        private readonly PluginExecutorInterface $pluginExecutor,
    ) {}

    public function connect(User $user, string $apiToken): void
    {
        $this->validateToken($apiToken);

        TodoistCredential::updateOrCreate(
            ['user_id' => $user->id],
            [
                'api_token' => Crypt::encryptString($apiToken),
            ],
        );
    }

    public function isConnected(User $user): bool
    {
        return TodoistCredential::where('user_id', $user->id)->exists();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listTasks(User $user, ?string $timezone = null): array
    {
        $token = $this->getDecryptedToken($user);

        $input = ['token' => $token];
        if ($timezone !== null && $timezone !== '') {
            $input['timezone'] = $timezone;
        }

        /** @var array<int, array<string, mixed>> $tasks */
        $tasks = $this->pluginExecutor->execute('todoist', 'list_tasks', $input);

        return $tasks;
    }

    /**
     * @return array<string, mixed>
     */
    public function createTask(User $user, string $content, ?string $description, int $priority): array
    {
        $token = $this->getDecryptedToken($user);

        /** @var array<string, mixed> $task */
        $task = $this->pluginExecutor->execute('todoist', 'create_task', [
            'token' => $token,
            'content' => $content,
            'description' => $description ?? '',
            'priority' => $priority,
        ]);

        return $task;
    }

    /**
     * @return array<string, mixed>
     */
    public function completeTask(User $user, string $taskId): array
    {
        return $this->runTaskMutation($user, 'complete_task', $taskId);
    }

    /**
     * @return array<string, mixed>
     */
    public function reopenTask(User $user, string $taskId): array
    {
        return $this->runTaskMutation($user, 'reopen_task', $taskId);
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteTask(User $user, string $taskId): array
    {
        return $this->runTaskMutation($user, 'delete_task', $taskId);
    }

    public function disconnect(User $user): void
    {
        TodoistCredential::where('user_id', $user->id)->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function runTaskMutation(User $user, string $action, string $taskId): array
    {
        $token = $this->getDecryptedToken($user);

        /** @var array<string, mixed> $result */
        $result = $this->pluginExecutor->execute('todoist', $action, [
            'token' => $token,
            'id' => $taskId,
        ]);

        return $result;
    }

    private function getDecryptedToken(User $user): string
    {
        $credential = TodoistCredential::where('user_id', $user->id)->first();

        if ($credential === null) {
            throw new \RuntimeException('Todoist is not connected');
        }

        return Crypt::decryptString($credential->api_token);
    }

    private function validateToken(string $token): void
    {
        $this->pluginExecutor->execute('todoist', 'list_tasks', [
            'token' => $token,
        ]);
    }
}
