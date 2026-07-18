<?php

namespace App\Console\Commands;

use App\Http\Controllers\PushController;
use App\Models\Episode;
use App\Models\Serie;
use App\Models\Watchlist;
use App\Services\TmdbService;
use Illuminate\Console\Command;

class SyncAllEpisodes extends Command
{
    protected $signature   = 'episodes:sync-all';
    protected $description = 'Sync episodes for all series with a TMDB ID';

    public function handle(TmdbService $tmdb): void
    {
        $series = Serie::whereNotNull('tmdb_id')->get();
        $this->info("Syncing episodes for {$series->count()} series...");
        $bar = $this->output->createProgressBar($series->count());
        $bar->start();

        $totalNew    = 0;
        $newPerSerie = [];

        foreach ($series as $serie) {
            try {
                $data         = $tmdb->getSeriesDetails((int) $serie->tmdb_id);
                $totalSeasons = $data['number_of_seasons'] ?? $serie->seasons ?? 1;

                for ($s = 1; $s <= $totalSeasons; $s++) {
                    $episodes = $tmdb->getSeasonEpisodes((int) $serie->tmdb_id, $s);
                    foreach ($episodes as $ep) {
                        $result = Episode::updateOrCreate(
                            ['serie_id' => $serie->id, 'season' => $s, 'episode' => $ep['episode_number']],
                            ['title' => $ep['name'] ?? null]
                        );
                        if ($result->wasRecentlyCreated) {
                            $totalNew++;
                            $newPerSerie[$serie->id] ??= ['serie' => $serie, 'count' => 0];
                            $newPerSerie[$serie->id]['count']++;
                        }
                    }
                }

                if (!empty($data['number_of_seasons']) && $serie->seasons != $data['number_of_seasons']) {
                    $serie->update(['seasons' => $data['number_of_seasons']]);
                }
            } catch (\Throwable $e) {
                $this->newLine();
                $this->warn("Error syncing {$serie->title}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. {$totalNew} new episode(s) added.");

        if (empty($newPerSerie)) {
            return;
        }

        $this->info('Sending push notifications...');
        foreach ($newPerSerie as ['serie' => $serie, 'count' => $count]) {
            $userIds = Watchlist::where('item_type', 'serie')
                ->where('item_id', $serie->id)
                ->pluck('user_id');

            if ($userIds->isEmpty()) continue;

            $label = $count === 1 ? 'episódio novo' : 'episódios novos';
            foreach ($userIds as $userId) {
                PushController::sendToUser(
                    $userId,
                    $serie->localTitle(),
                    "{$count} {$label} disponível",
                    "/series/{$serie->slug}"
                );
            }
        }
        $this->info('Notifications sent.');
    }
}