<?php

namespace App\Modules\User\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YandexCalendarCredential extends Model
{
    protected $table = 'yandex_calendar_credentials';

    protected $fillable = [
        'user_id',
        'email',
        'app_password',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
