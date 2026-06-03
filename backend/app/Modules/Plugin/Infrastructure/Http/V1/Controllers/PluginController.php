<?php

declare(strict_types=1);

namespace App\Modules\Plugin\Infrastructure\Http\V1\Controllers;

use App\Modules\Plugin\Application\DTOs\PluginExecuteDTO;
use App\Modules\Plugin\Application\UseCases\ExecutePluginUseCase;
use App\Modules\Plugin\Infrastructure\Http\V1\Requests\ExecutePluginRequest;
use Illuminate\Http\JsonResponse;

final class PluginController
{
    public function execute(
        string $name,
        string $action,
        ExecutePluginRequest $request,
        ExecutePluginUseCase $useCase,
    ): JsonResponse {
        $dto = PluginExecuteDTO::from([
            'name' => $name,
            'action' => $action,
            'input' => $request->validated('input', []),
        ]);

        $result = $useCase->execute($dto);

        return response()->json([
            'data' => $result,
        ]);
    }
}
