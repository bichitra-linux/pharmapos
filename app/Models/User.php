<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'company_id',
        'outlet_id',
        'name',
        'email',
        'password',
        'phone',
        'role',
        'pharmacist_registration',
        'permissions',
        'is_active',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
            'is_active' => 'boolean',
            'role' => UserRole::class,
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

    public function hasPermission(string $permission): bool
    {
        if ($this->role === UserRole::Owner) {
            return true;
        }

        $permissions = $this->permissions ?? [];

        if (in_array('*', $permissions)) {
            return true;
        }

        // Check wildcard permissions (e.g., 'sales.*' matches 'sales.create')
        $parts = explode('.', $permission);
        if (count($parts) === 2) {
            $wildcard = $parts[0].'.*';
            if (in_array($wildcard, $permissions)) {
                return true;
            }
        }

        return in_array($permission, $permissions);
    }

    public function isOwner(): bool
    {
        return $this->role === UserRole::Owner;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isPharmacist(): bool
    {
        return $this->role === UserRole::Pharmacist;
    }

    public function canDispense(): bool
    {
        return in_array($this->role, [UserRole::Owner, UserRole::Admin, UserRole::Pharmacist]);
    }
}
