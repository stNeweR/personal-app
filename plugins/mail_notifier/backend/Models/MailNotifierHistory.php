<?php

declare(strict_types=1);

namespace Plugins\MailNotifier\Models;

use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailNotifierHistory extends Model
{
    protected $table = 'mail_notifier_history';

    protected $fillable = [
        'user_id',
        'to',
        'subject',
        'body',
        'is_html',
        'status',
        'message_id',
    ];

    protected $casts = [
        'is_html' => 'boolean',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
