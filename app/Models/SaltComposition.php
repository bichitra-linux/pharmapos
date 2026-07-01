<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaltComposition extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    public function medicines(): HasMany
    {
        return $this->hasMany(Medicine::class);
    }
}
