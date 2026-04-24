<?php

namespace App\Modules\User\Infrastructure\Models;

use App\Modules\Pomodoro\Domain\Enums\StateValue;
use Database\Factories\UserStateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @class UserState
 *
 * @property int $user_id
 * @property StateValue $state_value
 */
final class UserState extends Model
{
    /** @use HasFactory<UserStateFactory> */
    use HasFactory;

    protected $table = 'user_states';

    protected $fillable = [
        'user_id',
        'state_value',
    ];

    protected $casts = [
        'state_value' => StateValue::class,
    ];

    protected static function newFactory(): UserStateFactory
    {
        return UserStateFactory::new();
    }
}
