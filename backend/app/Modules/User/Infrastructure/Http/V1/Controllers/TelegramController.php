<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Http\V1\Controllers;

use App\Modules\User\Application\DTOs\TelegramRegisterTokenResponseDTO;
use App\Modules\User\Application\DTOs\TelegramStatusResponseDTO;
use App\Modules\User\Application\UseCases\Auth\DisconnectTelegramUseCase;
use App\Modules\User\Application\UseCases\Auth\GenerateTelegramRegisterTokenUseCase;
use App\Modules\User\Application\UseCases\Auth\GetTelegramStatusUseCase;
use Illuminate\Http\JsonResponse;

final class TelegramController
{
    public function status(GetTelegramStatusUseCase $useCase): TelegramStatusResponseDTO
    {
        return $useCase->execute();
    }

    public function generateLinkToken(GenerateTelegramRegisterTokenUseCase $useCase): TelegramRegisterTokenResponseDTO
    {
        $data = $useCase->execute();

        return new TelegramRegisterTokenResponseDTO(
            token: $data->token,
            botName: $data->botName,
            expiresInMinutes: 15,
        );
    }

    public function disconnect(DisconnectTelegramUseCase $useCase): JsonResponse
    {
        $useCase->execute();

        return response()->json(['message' => 'Telegram disconnected']);
    }
}
