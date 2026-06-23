<?php

declare(strict_types=1);

namespace App\Modules\Plugin\Domain\Entities;

final class PluginManifest
{
    public function __construct(
        public readonly string $name,
        public readonly string $version,
        public readonly ?string $author,
        public readonly ?string $description,
        public readonly string $backendEntry,
        public readonly string $backendNamespace,
        public readonly string $frontendEntry,
        public readonly string $frontendWidget,
    ) {}

    /**
     * @param  array{name: string, version?: string, author?: string, description?: string, backend?: array{entry?: string, namespace?: string}, frontend?: array{entry?: string, widget?: string}}  $data
     */
    public static function fromArray(array $data): self
    {
        $backend = $data['backend'] ?? [];
        $frontend = $data['frontend'] ?? [];

        return new self(
            name: (string) $data['name'],
            version: $data['version'] ?? '1.0.0',
            author: $data['author'] ?? null,
            description: $data['description'] ?? null,
            backendEntry: $backend['entry'] ?? 'backend/Plugin.php',
            backendNamespace: $backend['namespace'] ?? '',
            frontendEntry: $frontend['entry'] ?? 'frontend/index.ts',
            frontendWidget: $frontend['widget'] ?? '',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'author' => $this->author,
            'description' => $this->description,
            'backend' => [
                'entry' => $this->backendEntry,
                'namespace' => $this->backendNamespace,
            ],
            'frontend' => [
                'entry' => $this->frontendEntry,
                'widget' => $this->frontendWidget,
            ],
        ];
    }
}
