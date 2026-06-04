<?php

namespace App\Models\Concerns;

trait HasLocalizations
{
    public function localTitle(): string
    {
        $locale = app()->getLocale();
        return $this->translations[$locale]['title'] ?? $this->title;
    }

    public function localSynopsis(): ?string
    {
        $locale = app()->getLocale();
        return $this->translations[$locale]['synopsis'] ?? $this->synopsis;
    }

    public function localGenres(): array
    {
        $locale = app()->getLocale();
        return $this->translations[$locale]['genres'] ?? $this->genres ?? [];
    }

    public function localPosterUrl(): ?string
    {
        $locale = app()->getLocale();
        return $this->translations[$locale]['poster_url'] ?? $this->poster_url ?? null;
    }
}
