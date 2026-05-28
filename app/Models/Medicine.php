<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DosageForm;
use App\Enums\ScheduleType;
use App\Enums\UnitType;
use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medicine extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'generic_name',
        'brand_name',
        'manufacturer_id',
        'salt_composition_id',
        'medicine_category_id',
        'dosage_form',
        'strength',
        'unit_type',
        'units_per_pack',
        'schedule_type',
        'hsn_code',
        'is_prescription_required',
        'is_active',
        'barcode',
        'image',
        'description',
        'storage_conditions',
        'is_temperature_sensitive',
    ];

    protected function casts(): array
    {
        return [
            'dosage_form' => DosageForm::class,
            'unit_type' => UnitType::class,
            'schedule_type' => ScheduleType::class,
            'is_prescription_required' => 'boolean',
            'is_temperature_sensitive' => 'boolean',
            'units_per_pack' => 'integer',
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

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function saltComposition(): BelongsTo
    {
        return $this->belongsTo(SaltComposition::class, 'salt_composition_id');
    }

    public function medicineCategory(): BelongsTo
    {
        return $this->belongsTo(MedicineCategory::class, 'medicine_category_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(MedicineBatch::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeBySchedule(Builder $query, ScheduleType $schedule): Builder
    {
        return $query->where('schedule_type', $schedule);
    }

    public function scopePrescriptionRequired(Builder $query): Builder
    {
        return $query->where('is_prescription_required', true);
    }

    public function getSubstitutesAttribute(): Collection
    {
        $medicineIds = Substitute::query()
            ->where('medicine_one_id', $this->id)
            ->orWhere('medicine_two_id', $this->id)
            ->pluck('medicine_one_id')
            ->merge(
                Substitute::query()
                    ->where('medicine_one_id', $this->id)
                    ->orWhere('medicine_two_id', $this->id)
                    ->pluck('medicine_two_id')
            )
            ->unique()
            ->reject(fn (int $id) => $id === $this->id);

        return static::whereIn('id', $medicineIds)->get();
    }
}
