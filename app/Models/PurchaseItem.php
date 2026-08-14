<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'medicine_id',
        'batch_number',
        'manufacturing_date',
        'expiry_date',
        'quantity',
        'received_quantity',
        'purchase_price',
        'mrp',
        'selling_price',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'manufacturing_date' => 'date',
            'expiry_date' => 'date',
            'quantity' => 'decimal:2',
            'received_quantity' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'mrp' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    // ponytail: UI reads unit_price/total_amount; DB stores purchase_price/total
    public function getUnitPriceAttribute(): float
    {
        return (float) $this->purchase_price;
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) $this->total;
    }
}
