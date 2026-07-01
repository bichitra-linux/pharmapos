<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierReturn extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'outlet_id',
        'supplier_id',
        'purchase_id',
        'return_number',
        'reason',
        'total_amount',
        'refund_status',
        'notes',
        'return_date',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'return_date' => 'date',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierReturnItem::class);
    }
}
