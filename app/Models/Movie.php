<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizations;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasLocalizations;

    protected $fillable = [
        'title', 'original_title', 'year', 'genres',
        'synopsis', 'poster_url', 'tmdb_id', 'rating', 'duration', 'translations',
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
}
