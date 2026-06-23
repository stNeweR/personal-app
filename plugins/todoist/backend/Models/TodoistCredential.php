<?php

declare(strict_types=1);

namespace Plugins\Todoist\Models;

use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TodoistCredential extends Model
{
    protected $table = 'todoist_credentials';

    protected $fillable = [
        'user_id',
        'api_token',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
