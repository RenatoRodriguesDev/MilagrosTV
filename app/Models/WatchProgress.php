<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatchProgress extends Model
{
    protected $fillable = ['user_id', 'episode_id', 'position', 'duration', 'completed'];

    protected $casts = ['completed' => 'boolean'];

    public function episode()
    {
        return $this->belongsTo(Episode::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getPercentAttribute(): int
    {
        if (!$this->duration) return 0;
        return min(100, (int) round($this->position / $this->duration * 100));
    }
}
