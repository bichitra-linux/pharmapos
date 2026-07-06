<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPageRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'landing_page_id',
        'content',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
        ];
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'created_by');
    }
}
