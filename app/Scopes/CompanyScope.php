<?php

declare(strict_types=1);

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CompanyScope implements Scope
{
    private static bool $resolving = false;

    public function apply(Builder $builder, Model $model): void
    {
        if (self::$resolving) {
            return;
        }

        self::$resolving = true;

        try {
            $user = request()?->user();

            if (! $user && Auth::guard('sanctum')->check()) {
                $user = Auth::guard('sanctum')->user();
            }

            if ($user && $user->company_id) {
                $builder->where($model->getTable().'.company_id', $user->company_id);
            }
        } finally {
            self::$resolving = false;
        }
    }
}
