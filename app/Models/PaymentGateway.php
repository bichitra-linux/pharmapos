<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_active',
        'is_sandbox',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_sandbox' => 'boolean',
            'config' => 'array',
        ];
    }
}
