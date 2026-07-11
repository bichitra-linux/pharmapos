<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditableObserver
{
    public function created(Model $model): void
    {
        $this->log($model, 'created', null, $this->snapshot($model));
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();

        if ($changes === [] && $model->wasChanged() === false) {
            return;
        }

        $original = array_intersect_key($model->getOriginal(), $changes);
        $updated = array_intersect_key($changes, $model->getOriginal());

        $this->log($model, 'updated', $original, $updated);
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'deleted', $this->snapshot($model), null);
    }

    public function restored(Model $model): void
    {
        $this->log($model, 'restored', null, $this->snapshot($model));
    }

    private function log(Model $model, string $action, ?array $oldValues, ?array $newValues): void
    {
        $companyId = $model->company_id ?? config('app.current_company_id');

        // ponytail: skip audit if no tenant context (e.g. Company model itself, seeder runs)
        if (! $companyId) {
            return;
        }

        $request = Request::instance();

        AuditLog::create([
            'company_id' => $companyId,
            'user_id' => optional($request->user())->id ?? optional(auth()->user())->id,
            'action' => $action,
            'model_type' => $model->getMorphClass(),
            'model_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'created_at' => now(),
        ]);
    }

    private function snapshot(Model $model): array
    {
        $hidden = array_merge($model->getHidden(), [
            'password', 'remember_token', 'api_token',
        ]);

        return collect($model->getAttributes())
            ->reject(fn ($_, $key) => in_array($key, $hidden, true))
            ->all();
    }
}
