<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'medicine_id',
        'outlet_id',
        'batch_number',
        'manufacturing_date',
        'expiry_date',
        'quantity_in_stock',
        'quantity_in_pieces',
        'received_pieces',
        'purchase_price_per_unit',
        'mrp_per_unit',
        'selling_price_per_unit',
        'barcode',
        'reorder_level',
        'supplier_id',
        'purchase_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'manufacturing_date' => 'date',
            'quantity_in_stock' => 'decimal:2',
            'quantity_in_pieces' => 'decimal:2',
            'received_pieces' => 'decimal:2',
            'purchase_price_per_unit' => 'decimal:2',
            'mrp_per_unit' => 'decimal:2',
            'selling_price_per_unit' => 'decimal:2',
            'is_active' => 'boolean',
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

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeExpiringSoon(Builder $query, int $days = 90): Builder
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now());
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('quantity_in_stock', '>', 0);
    }

    public function scopeFifo(Builder $query): Builder
    {
        return $query->orderBy('expiry_date', 'asc');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date->isPast();
    }

    public function daysUntilExpiry(): int
    {
        return (int) now()->diffInDays($this->expiry_date, false);
    }
}
