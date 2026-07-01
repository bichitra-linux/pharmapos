<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_id',
        'medicine_id',
        'batch_id',
        'quantity',
        'amount',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function customerReturn(): BelongsTo
    {
        return $this->belongsTo(CustomerReturn::class, 'return_id');
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
