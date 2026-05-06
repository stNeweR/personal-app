<?php

namespace App\Modules\User\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @class TelegramLinkToken
 *
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property \DateTime $expires_at
 * @property \DateTime|null $used_at
 */
final class TelegramLinkToken extends Model
{
    protected $table = 'telegram_link_tokens';

    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
