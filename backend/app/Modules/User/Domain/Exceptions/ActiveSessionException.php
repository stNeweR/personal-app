<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Exceptions;

use RuntimeException;

final class ActiveSessionException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('У вас уже есть активная сессия на другом устройстве. Выйдите из аккаунта на другом устройстве или подождите.', 403);
    }
}
