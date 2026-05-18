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

    public function isExternalUrl(): bool
    {
        return $this->video_path && str_starts_with($this->video_path, 'http');
    }

    public function embedUrl(): ?string
    {
        if (!$this->video_path) return null;

        // ok.ru: https://ok.ru/video/123 → https://ok.ru/videoembed/123
        if (preg_match('#ok\.ru/video/(\d+)#', $this->video_path, $m)) {
            return "https://ok.ru/videoembed/{$m[1]}";
        }

        // YouTube: https://youtube.com/watch?v=ID ou youtu.be/ID
        if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]+)#', $this->video_path, $m)) {
            return "https://www.youtube.com/embed/{$m[1]}";
        }

        // Vimeo
        if (preg_match('#vimeo\.com/(\d+)#', $this->video_path, $m)) {
            return "https://player.vimeo.com/video/{$m[1]}";
        }

        // Já é um embed ou URL directa
        return $this->video_path;
    }

    public function hasVideo(): bool
    {
        if ($this->isExternalUrl()) return true;
        return $this->video_path && file_exists(storage_path('app/videos/' . $this->video_path));
    }
}
