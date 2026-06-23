<?php

declare(strict_types=1);

namespace Plugins\Todoist\Services;

use App\Modules\User\Infrastructure\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Plugins\Todoist\Models\TodoistCredential;

final class TodoistService
{
    private const string API_BASE = 'https://api.todoist.com/api/v1';

    private const int TIMEOUT = 15;

    public function isConnected(User $user): bool
    {
        return TodoistCredential::where('user_id', $user->id)->exists();
    }

    public function connect(User $user, string $apiToken): void
    {
        // Validate token by making a real API call.
        $this->listTasksRaw($apiToken);

        TodoistCredential::updateOrCreate(
            ['user_id' => $user->id],
            ['api_token' => Crypt::encryptString($apiToken)],
        );
    }

    public function disconnect(User $user): void
    {
        TodoistCredential::where('user_id', $user->id)->delete();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listTasks(User $user, ?string $timezone = null): array
    {
        $token = $this->getDecryptedToken($user);
        $items = $this->listTasksRaw($token);

        $today = Carbon::now($timezone ?? 'UTC')->format('Y-m-d');

        $filtered = array_filter($items, static function (array $item) use ($today): bool {
            if (! isset($item['due']) || $item['due'] === null) {
                return false;
            }

            /** @var string $dueDate */
            $dueDate = $item['due']['date'] ?? '';
            if ($dueDate === '' && ! empty($item['due']['datetime'])) {
                $dueDate = substr((string) $item['due']['datetime'], 0, 10);
            }

            return $dueDate !== '' && $dueDate <= $today;
        });

        usort($filtered, static function (array $a, array $b): int {
            return ($b['priority'] ?? 1) <=> ($a['priority'] ?? 1);
        });

        return array_values(array_map(fn (array $item) => $this->normalizeTask($item), $filtered));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function listTasksRaw(string $token): array
    {
        $response = Http::withToken($token)
            ->asForm()
            ->timeout(self::TIMEOUT)
            ->post(self::API_BASE.'/sync', [
                'resource_types' => '["items"]',
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Todoist API error: '.$response->body());
        }

        return $response->json('items') ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function createTask(User $user, string $content, ?string $description, int $priority): array
    {
        $token = $this->getDecryptedToken($user);

        $args = ['content' => $content];
        if ($description !== null && $description !== '') {
            $args['description'] = $description;
        }
        if ($priority > 0) {
            $args['priority'] = $priority;
        }

        $tempId = 'tmp-'.(string) Str::uuid()->getHex().'-'.now()->getTimestampMs();
        $cmdUuid = (string) Str::uuid()->getHex();

        $result = $this->runCommand($token, 'item_add', $args, $tempId, $cmdUuid);

        $realId = $result['temp_id_mapping'][$tempId] ?? null;
        if (! is_string($realId) || $realId === '') {
            throw new \RuntimeException('Todoist create task: no temp_id mapping returned');
        }

        return $this->normalizeTask([
            'id' => $realId,
            'content' => $content,
            'description' => $description ?? '',
            'priority' => $priority,
            'checked' => false,
            'due' => null,
            'labels' => [],
            'project_id' => '',
            'section_id' => '',
            'parent_id' => '',
            'child_order' => 0,
            'day_order' => 0,
            'added_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function completeTask(User $user, string $taskId): array
    {
        $this->runMutation($user, 'item_close', $taskId);

        return ['id' => $taskId, 'completed' => 'true'];
    }

    /**
     * @return array<string, mixed>
     */
    public function reopenTask(User $user, string $taskId): array
    {
        $this->runMutation($user, 'item_uncomplete', $taskId);

        return ['id' => $taskId, 'reopened' => 'true'];
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteTask(User $user, string $taskId): array
    {
        $this->runMutation($user, 'item_delete', $taskId);

        return ['id' => $taskId, 'deleted' => 'true'];
    }

    private function runMutation(User $user, string $commandType, string $taskId): void
    {
        $token = $this->getDecryptedToken($user);
        $this->runCommand($token, $commandType, ['id' => $taskId]);
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function runCommand(string $token, string $commandType, array $args, ?string $tempId = null, ?string $cmdUuid = null): array
    {
        $cmdUuid ??= (string) Str::uuid()->getHex();

        $command = [
            'type' => $commandType,
            'uuid' => $cmdUuid,
            'args' => $args,
        ];

        if ($tempId !== null && $tempId !== '') {
            $command['temp_id'] = $tempId;
        }

        $response = Http::withToken($token)
            ->asForm()
            ->timeout(self::TIMEOUT)
            ->post(self::API_BASE.'/sync', [
                'commands' => json_encode([$command]),
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Todoist API error: '.$response->body());
        }

        /** @var array<string, mixed> $data */
        $data = $response->json() ?? [];

        /** @var array<string, string> $syncStatus */
        $syncStatus = $data['sync_status'] ?? [];

        if (($syncStatus[$cmdUuid] ?? null) !== 'ok') {
            throw new \RuntimeException("Todoist {$commandType} failed: ".json_encode($syncStatus));
        }

        return $data;
    }

    private function getDecryptedToken(User $user): string
    {
        $credential = TodoistCredential::where('user_id', $user->id)->first();

        if ($credential === null) {
            throw new \RuntimeException('Todoist is not connected');
        }

        return Crypt::decryptString($credential->api_token);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function normalizeTask(array $item): array
    {
        $id = (string) ($item['id'] ?? '');

        return [
            'id' => $id,
            'content' => (string) ($item['content'] ?? ''),
            'description' => (string) ($item['description'] ?? ''),
            'checked' => (bool) ($item['checked'] ?? false),
            'priority' => (int) ($item['priority'] ?? 1),
            'due' => $item['due'] ?? null,
            'project_id' => (string) ($item['project_id'] ?? ''),
            'section_id' => (string) ($item['section_id'] ?? ''),
            'parent_id' => (string) ($item['parent_id'] ?? ''),
            'order' => (int) ($item['child_order'] ?? 0),
            'labels' => $item['labels'] ?? [],
            'url' => $id !== '' ? "https://app.todoist.com/app/task/{$id}" : '',
            'comment_count' => 0,
            'created_at' => (string) ($item['added_at'] ?? now()->toIso8601String()),
            'creator_id' => (string) ($item['added_by_uid'] ?? ''),
            'assignee_id' => (string) ($item['assigned_by_uid'] ?? ''),
            'assigner_id' => '',
        ];
    }
}
