<?php

namespace App\Modules\User\Infrastructure\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @class User
 *
 * @property int $id
 * @property int $telegram_id
 */
final class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'telegram_id',
    ];

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
