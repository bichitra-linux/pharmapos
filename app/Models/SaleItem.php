<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'medicine_id',
        'batch_id',
        'quantity',
        'unit_type',
        'mrp',
        'selling_price',
        'discount',
        'vat',
        'total',
        'prescription_required',
        'units_per_pack',
        'sell_mode',
        'pieces_quantity',
        'medicine_name',
        'medicine_generic_name',
        'medicine_strength',
        'medicine_manufacturer',
        'medicine_dosage_form',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'mrp' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'discount' => 'decimal:2',
            'vat' => 'decimal:2',
            'total' => 'decimal:2',
            'prescription_required' => 'boolean',
            'units_per_pack' => 'integer',
            'pieces_quantity' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(MedicineBatch::class);
    }
}
