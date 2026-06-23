<?php

declare(strict_types=1);

namespace App\Modules\Plugin\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class Plugin extends Model
{
    protected $table = 'plugins';

    protected $fillable = [
        'name',
        'version',
        'author',
        'description',
        'enabled',
        'config',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'config' => 'array',
    ];
}
