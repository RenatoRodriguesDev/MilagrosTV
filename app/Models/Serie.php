<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Serie extends Model
{
    use HasLocalizations;

    protected $fillable = [
        'title', 'original_title', 'year', 'genres', 'slug',
        'synopsis', 'poster_url', 'trailer_url', 'tmdb_id', 'rating', 'seasons', 'translations', 'piratahub_slug', 'cinemacity_id',
    ];

    public function getRouteKeyName(): string { return 'slug'; }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('slug', $value)->orWhere('id', $value)->firstOrFail();
    }

    protected static function booted(): void
    {
        static::creating(function ($m) {
            if (!$m->slug) $m->slug = static::uniqueSlug($m->title);
        });
        static::updating(function ($m) {
            if ($m->isDirty('title') && !$m->getOriginal('slug')) {
                $m->slug = static::uniqueSlug($m->title, $m->id);
            }
        });
    }

    public static function uniqueSlug(string $title, ?int $excludeId = null): string
    {
        $base = Str::slug($title) ?: 'serie';
        $slug = $base; $i = 2;
        while (static::where('slug', $slug)->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))->exists()) {
            $slug = "{$base}-" . $i++;
        }
        return $slug;
    }

    protected $casts = [
        'genres'       => 'array',
        'translations' => 'array',
        'rating'       => 'float',
    ];

    public function episodes()
    {
        return $this->hasMany(Episode::class)->orderBy('season')->orderBy('episode');
    }

    public function getGenresListAttribute(): string
    {
        return implode(', ', $this->genres ?? []);
    }
}
