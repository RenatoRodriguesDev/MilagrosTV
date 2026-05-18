<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Episode extends Model
{
    protected $fillable = ['serie_id', 'season', 'episode', 'title', 'video_path'];

    public function serie()
    {
        return $this->belongsTo(Serie::class);
    }

    public function getLabelAttribute(): string
    {
        $code = "T{$this->season}E{$this->episode}";
        return $this->title ? "{$code} - {$this->title}" : $code;
    }

    public function hasVideo(): bool
    {
        return $this->video_path && file_exists(storage_path('app/videos/' . $this->video_path));
    }
}
