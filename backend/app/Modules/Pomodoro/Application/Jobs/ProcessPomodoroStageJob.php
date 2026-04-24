<?php

namespace App\Modules\Pomodoro\Application\Jobs;

use App\Modules\Pomodoro\Application\Service\PomodoroStageService;
use App\Modules\Pomodoro\Domain\Enums\PomodoroStatusValue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessPomodoroStageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $sessionId,
        public readonly int $currentCycle = 1,
        public readonly PomodoroStatusValue $currentStatus = PomodoroStatusValue::WORK
    ) {}

    public function handle(PomodoroStageService $service): void
    {
        $service->resolve($this->sessionId, $this->currentCycle, $this->currentStatus);
    }
}
