<?php

namespace App\Modules\Pomodoro\Infrastructure\Models;

use App\Modules\Pomodoro\Domain\Enums\PomodoroStatusValue;
use App\Modules\User\Infrastructure\Models\User;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @class PomodoroSession
 *
 * @property int $id
 * @property int $user_id
 * @property PomodoroStatusValue $current_status
 * @property DateTime|null $start_at
 * @property DateTime|null $end_at
 * @property int $current_cycle
 */
final class PomodoroSession extends Model
{
    protected $table = 'pomodoro_session';

    protected $fillable = [
        'user_id',
        'current_status',
        'start_at',
        'end_at',
        'current_cycle',
    ];

    protected $casts = [
        'current_status' => PomodoroStatusValue::class,
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'current_cycle' => 'integer',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
