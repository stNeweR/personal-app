<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Infrastructure\Http\V1\Controllers;

use App\Modules\Pomodoro\Application\DTOs\CreatePomodoroSessionRequestDTO;
use App\Modules\Pomodoro\Application\DTOs\PomodoroSessionDTO;
use App\Modules\Pomodoro\Application\DTOs\PomodoroSettingsResponseDTO;
use App\Modules\Pomodoro\Application\DTOs\SavePomodoroSettingsDTO;
use App\Modules\Pomodoro\Application\DTOs\TodaySessionsResponseDTO;
use App\Modules\Pomodoro\Application\DTOs\UpdatePomodoroSessionRequestDTO;
use App\Modules\Pomodoro\Application\UseCases\CreatePomodoroSessionUseCase;
use App\Modules\Pomodoro\Application\UseCases\GetPomodoroSettingsForWebUseCase;
use App\Modules\Pomodoro\Application\UseCases\GetTodaySessionsForUserUseCase;
use App\Modules\Pomodoro\Application\UseCases\SavePomodoroSettingsUseCase;
use App\Modules\Pomodoro\Application\UseCases\UpdatePomodoroSessionUseCase;
use App\Modules\Pomodoro\Infrastructure\Http\V1\Requests\CreatePomodoroSessionRequest;
use App\Modules\Pomodoro\Infrastructure\Http\V1\Requests\SavePomodoroSettingsRequest;
use App\Modules\Pomodoro\Infrastructure\Http\V1\Requests\UpdatePomodoroSessionRequest;
use Illuminate\Http\JsonResponse;

final class PomodoroController
{
    public function today(GetTodaySessionsForUserUseCase $useCase): TodaySessionsResponseDTO
    {
        return $useCase->execute();
    }

    public function getSettings(GetPomodoroSettingsForWebUseCase $useCase): PomodoroSettingsResponseDTO|JsonResponse
    {
        $settings = $useCase->execute();

        if ($settings === null) {
            return response()->json(['message' => 'Settings not found'], 404);
        }

        return $settings;
    }

    public function saveSettings(SavePomodoroSettingsRequest $request, SavePomodoroSettingsUseCase $useCase): PomodoroSettingsResponseDTO
    {
        return $useCase->execute(SavePomodoroSettingsDTO::from($request->validated()));
    }

    public function createSession(CreatePomodoroSessionRequest $request, CreatePomodoroSessionUseCase $useCase): PomodoroSessionDTO
    {
        return $useCase->execute(CreatePomodoroSessionRequestDTO::from($request->validated()));
    }

    public function updateSession(int $id, UpdatePomodoroSessionRequest $request, UpdatePomodoroSessionUseCase $useCase): PomodoroSessionDTO
    {
        return $useCase->execute($id, UpdatePomodoroSessionRequestDTO::from($request->validated()));
    }
}
