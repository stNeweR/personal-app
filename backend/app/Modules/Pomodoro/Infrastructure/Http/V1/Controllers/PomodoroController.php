<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Infrastructure\Http\V1\Controllers;

use App\Modules\Pomodoro\Application\DTOs\TodaySessionsResponseDTO;
use App\Modules\Pomodoro\Application\UseCases\GetTodaySessionsForUserUseCase;

final class PomodoroController
{
    public function today(GetTodaySessionsForUserUseCase $useCase): TodaySessionsResponseDTO
    {
        return $useCase->execute();
    }
}
