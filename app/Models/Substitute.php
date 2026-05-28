<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Substitute extends Model
{
    use HasFactory;

    protected $fillable = [
        'medicine_one_id',
        'medicine_two_id',
        'notes',
    ];

    public function medicineOne(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_one_id');
    }

    public function medicineTwo(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_two_id');
    }
}
