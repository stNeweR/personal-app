<?php

declare(strict_types=1);

namespace App\Modules\Plugin\Application\UseCases;

use App\Modules\Plugin\Application\DTOs\PluginExecuteDTO;
use App\Modules\Plugin\Domain\Contracts\PluginExecutorInterface;

final class ExecutePluginUseCase
{
    public function __construct(
        private readonly PluginExecutorInterface $executor,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(PluginExecuteDTO $dto): array
    {
        return $this->executor->execute($dto->name, $dto->action, $dto->input);
    }
}
