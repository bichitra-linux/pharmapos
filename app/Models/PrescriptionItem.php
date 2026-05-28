<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'salt_composition_id',
        'medicine_id',
        'dosage_form',
        'strength',
        'dosage_instructions',
        'duration_days',
        'quantity_prescribed',
        'quantity_dispensed',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
            'quantity_prescribed' => 'integer',
            'quantity_dispensed' => 'integer',
        ];
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function saltComposition(): BelongsTo
    {
        return $this->belongsTo(SaltComposition::class, 'salt_composition_id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }
}
