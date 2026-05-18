<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizations;
use Illuminate\Database\Eloquent\Model;

class Serie extends Model
{
    use HasLocalizations;

    protected $fillable = [
        'title', 'original_title', 'year', 'genres',
        'synopsis', 'poster_url', 'tmdb_id', 'rating', 'seasons', 'translations',
    ];

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
