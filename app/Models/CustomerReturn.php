<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerReturn extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'outlet_id',
        'sale_id',
        'customer_id',
        'processed_by',
        'return_number',
        'reason',
        'total_amount',
        'refund_amount',
        'refund_method',
        'return_date',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'refund_amount' => 'decimal:2',
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

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerReturnItem::class, 'return_id');
    }
}
