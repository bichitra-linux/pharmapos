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
        'medicine_id_1',
        'medicine_id_2',
        'notes',
    ];

    public function medicineOne(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id_1');
    }

    public function medicineTwo(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id_2');
    }
}
