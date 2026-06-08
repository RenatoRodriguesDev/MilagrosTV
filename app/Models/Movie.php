<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizations;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasLocalizations;

    protected $fillable = [
        'title', 'original_title', 'year', 'genres', 'slug',
        'synopsis', 'poster_url', 'trailer_url', 'video_path', 'tmdb_id', 'rating', 'duration', 'translations', 'piratahub_url', 'cinemacity_id',
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
        $base = \Illuminate\Support\Str::slug($title) ?: 'movie';
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

    public function getGenresListAttribute(): string
    {
        return implode(', ', $this->genres ?? []);
    }

    public function hasVideo(): bool
    {
        if (!$this->video_path) return false;
        if (str_starts_with($this->video_path, 'http')) return true;
        return file_exists(storage_path('app/videos/' . $this->video_path));
    }
}
