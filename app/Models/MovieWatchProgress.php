<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovieWatchProgress extends Model
{
    protected $table    = 'movie_watch_progress';
    protected $fillable = ['user_id', 'movie_id', 'position', 'duration', 'completed'];
    protected $casts    = ['completed' => 'boolean'];

    public function user()  { return $this->belongsTo(User::class); }
    public function movie() { return $this->belongsTo(Movie::class); }

    public function getPercentAttribute(): int
    {
        if (!$this->duration) return 0;
        return (int) min(100, round($this->position / $this->duration * 100));
    }
}
