<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NarcoticsRegister extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'company_id',
        'outlet_id',
        'sale_id',
        'medicine_id',
        'batch_id',
        'patient_name',
        'patient_address',
        'doctor_name',
        'prescription_number',
        'quantity',
        'balance',
        'dispensed_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'balance' => 'decimal:2',
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

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(MedicineBatch::class);
    }

    public function dispensedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispensed_by');
    }
}
