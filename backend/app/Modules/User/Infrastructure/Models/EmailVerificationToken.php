<?php

namespace App\Modules\User\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @class EmailVerificationToken
 *
 * @property int $id
 * @property int $user_id
 * @property string $email
 * @property string $token
 * @property \DateTime $expires_at
 * @property \DateTime|null $used_at
 */
final class EmailVerificationToken extends Model
{
    protected $table = 'email_verification_tokens';

    protected $fillable = [
        'user_id',
        'email',
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
