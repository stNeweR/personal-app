<?php

namespace App\Modules\Pomodoro\Domain\Enums;

use App\Modules\Pomodoro\Application\Handlers\State\AwaitingBreakDurationStateHandler;
use App\Modules\Pomodoro\Application\Handlers\State\AwaitingCyclesBeforeLongBreakStateHandler;
use App\Modules\Pomodoro\Application\Handlers\State\AwaitingLongBreakDurationStateHandler;
use App\Modules\Pomodoro\Application\Handlers\State\AwaitingRepeatsCountStateHandler;
use App\Modules\Pomodoro\Application\Handlers\State\AwaitingWorkDurationStateHandler;

enum StateValue: string
{
    case AWAITING_WORK_DURATION = 'awaiting_work_duration';
    case AWAITING_BREAK_DURATION = 'awaiting_break_duration';
    case AWAITING_REPEATS_COUNT = 'awaiting_repeats_count';
    case AWAITING_LONG_BREAK_DURATION = 'awaiting_long_break_duration';
    case AWAITING_CYCLES_BEFORE_LONG_BREAK = 'awaiting_cycles_before_long_break';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return [
            self::AWAITING_WORK_DURATION->value,
            self::AWAITING_BREAK_DURATION->value,
            self::AWAITING_REPEATS_COUNT->value,
            self::AWAITING_LONG_BREAK_DURATION->value,
            self::AWAITING_CYCLES_BEFORE_LONG_BREAK->value,
        ];
    }

    public function getHandler(): string
    {
        return match ($this) {
            self::AWAITING_WORK_DURATION => AwaitingWorkDurationStateHandler::class,
            self::AWAITING_BREAK_DURATION => AwaitingBreakDurationStateHandler::class,
            self::AWAITING_REPEATS_COUNT => AwaitingRepeatsCountStateHandler::class,
            self::AWAITING_LONG_BREAK_DURATION => AwaitingLongBreakDurationStateHandler::class,
            self::AWAITING_CYCLES_BEFORE_LONG_BREAK => AwaitingCyclesBeforeLongBreakStateHandler::class,
        };
    }
}
