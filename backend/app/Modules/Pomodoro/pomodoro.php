<?php

declare(strict_types=1);

use App\Modules\Pomodoro\Application\Handlers\State\AwaitingBreakDurationStateHandler;
use App\Modules\Pomodoro\Application\Handlers\State\AwaitingCyclesBeforeLongBreakStateHandler;
use App\Modules\Pomodoro\Application\Handlers\State\AwaitingLongBreakDurationStateHandler;
use App\Modules\Pomodoro\Application\Handlers\State\AwaitingRepeatsCountStateHandler;
use App\Modules\Pomodoro\Application\Handlers\State\AwaitingWorkDurationStateHandler;
use App\Modules\Pomodoro\Domain\Enums\StateValue;

return [
    'state_handlers' => [
        StateValue::AWAITING_WORK_DURATION->value => AwaitingWorkDurationStateHandler::class,
        StateValue::AWAITING_BREAK_DURATION->value => AwaitingBreakDurationStateHandler::class,
        StateValue::AWAITING_REPEATS_COUNT->value => AwaitingRepeatsCountStateHandler::class,
        StateValue::AWAITING_LONG_BREAK_DURATION->value => AwaitingLongBreakDurationStateHandler::class,
        StateValue::AWAITING_CYCLES_BEFORE_LONG_BREAK->value => AwaitingCyclesBeforeLongBreakStateHandler::class,
    ],
];
