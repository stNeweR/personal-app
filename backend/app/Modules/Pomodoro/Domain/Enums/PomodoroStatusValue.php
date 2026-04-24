<?php

namespace App\Modules\Pomodoro\Domain\Enums;

enum PomodoroStatusValue: string
{
    case PAUSED = 'paused';
    case FINISHED = 'finished';
    case WORK = 'work';
    case BREAK = 'break';
    case LONG_BREAK = 'long_break';
}
