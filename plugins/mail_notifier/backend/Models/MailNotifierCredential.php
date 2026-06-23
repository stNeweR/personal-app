<?php

declare(strict_types=1);

namespace Plugins\MailNotifier\Models;

use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailNotifierCredential extends Model
{
    protected $table = 'mail_notifier_credentials';

    protected $fillable = [
        'user_id',
        'verification_token',
        'token_expires_at',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
