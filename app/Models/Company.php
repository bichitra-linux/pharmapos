<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'address',
        'city',
        'state',
        'country',
        'local_level',
        'phone',
        'phone_country_code',
        'email',
        'pan_number',
        'vat_number',
        'drug_license_number',
        'pharmacy_license_number',
        'pharmacist_name',
        'pharmacist_registration_number',
        'subscription_plan_id',
        'subscription_expires_at',
        'settings',
        'country',
        'state',
        'local_level',
        'registration_number',
        'google_maps_link',
        'is_active',
        'suspended_at',
        'suspension_reason',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
            'subscription_expires_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function outlets(): HasMany
    {
        return $this->hasMany(Outlet::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function medicines(): HasMany
    {
        return $this->hasMany(Medicine::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function isSubscriptionActive(): bool
    {
        return $this->is_active
            && $this->subscription_expires_at !== null
            && $this->subscription_expires_at->isFuture();
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function suspend(string $reason = ''): void
    {
        $this->update([
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);
    }

    public function activate(): void
    {
        $this->update([
            'suspended_at' => null,
            'suspension_reason' => null,
            'is_active' => true,
        ]);
    }
}
