<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;

class UserObserver extends AuditableObserver
{
    public static function modelClass(): string
    {
        return User::class;
    }
}
