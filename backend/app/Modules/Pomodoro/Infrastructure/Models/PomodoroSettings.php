<?php

namespace App\Modules\Pomodoro\Infrastructure\Models;

use Database\Factories\PomodoroSettingsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @class PomodoroSettings
 *
 * @property int $user_id
 * @property int $work_duration
 * @property int $break_duration
 * @property int $repeats_count
 * @property ?int $long_break_duration
 * @property ?int $cycles_before_long_break
 */
final class PomodoroSettings extends Model
{
    /** @use HasFactory<PomodoroSettingsFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_duration',
        'break_duration',
        'repeats_count',
        'long_break_duration',
        'cycles_before_long_break',
    ];

    protected static function newFactory(): PomodoroSettingsFactory
    {
        return PomodoroSettingsFactory::new();
    }
}
