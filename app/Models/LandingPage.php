<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandingPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'theme_overrides',
        'preview_token',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'theme_overrides' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(LandingPageRevision::class);
    }

    public function publish(): void
    {
        $this->update(['published_at' => now()]);
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    public function createRevision(int $superAdminId): void
    {
        $this->revisions()->create([
            'content' => $this->content,
            'created_by' => $superAdminId,
        ]);
    }
}
