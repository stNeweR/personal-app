<?php

declare(strict_types=1);

namespace Plugins\Playlist\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\User\Infrastructure\Models\User;

class Playlist extends Model
{
    protected $table = 'playlists';

    protected $fillable = [
        'user_id',
        'url',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
