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

            $companyId = $user?->company_id ?? config('app.current_company_id');

            if ($companyId) {
                $builder->where($model->getTable().'.company_id', $companyId);
            }
        } finally {
            self::$resolving = false;
        }
    }
}
