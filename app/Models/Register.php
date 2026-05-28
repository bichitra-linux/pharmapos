<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RegisterStatus;
use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Register extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'outlet_id',
        'user_id',
        'opening_balance',
        'closing_balance',
        'total_sales',
        'total_returns',
        'total_cash',
        'total_digital',
        'status',
        'opened_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RegisterStatus::class,
            'opening_balance' => 'decimal:2',
            'closing_balance' => 'decimal:2',
            'total_sales' => 'decimal:2',
            'total_returns' => 'decimal:2',
            'total_cash' => 'decimal:2',
            'total_digital' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function isOpen(): bool
    {
        return $this->status === RegisterStatus::Open;
    }
}
