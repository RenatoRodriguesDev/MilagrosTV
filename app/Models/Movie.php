<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizations;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasLocalizations;

    protected $fillable = [
        'title', 'original_title', 'year', 'genres',
        'synopsis', 'poster_url', 'trailer_url', 'video_path', 'tmdb_id', 'rating', 'duration', 'translations',
    ];

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
