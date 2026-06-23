<?php

declare(strict_types=1);

namespace Plugins\Telegram\Models;

use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramNotifierHistory extends Model
{
    protected $table = 'telegram_notifier_history';

    protected $fillable = [
        'user_id',
        'chat_id',
        'message',
        'status',
        'message_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
