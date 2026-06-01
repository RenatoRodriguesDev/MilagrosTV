<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Models\Serie;
use App\Services\TmdbService;
use Illuminate\Console\Command;

class SyncSpanishTranslations extends Command
{
    protected $signature   = 'translations:sync-es';
    protected $description = 'Re-fetch Spanish translations (es-MX) for all series and movies';

    public function handle(TmdbService $tmdb): void
    {
        $movies = Movie::whereNotNull('tmdb_id')->get();
        $series = Serie::whereNotNull('tmdb_id')->get();
        $total  = $movies->count() + $series->count();

        $this->info("Syncing {$total} items (es-MX)...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($movies as $movie) {
            try {
                $movie->update(['translations' => $tmdb->fetchTranslations((int) $movie->tmdb_id, 'movie')]);
            } catch (\Throwable) {}
            $bar->advance();
        }

        foreach ($series as $serie) {
            try {
                $serie->update(['translations' => $tmdb->fetchTranslations((int) $serie->tmdb_id, 'tv')]);
            } catch (\Throwable) {}
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done.');
    }
}
