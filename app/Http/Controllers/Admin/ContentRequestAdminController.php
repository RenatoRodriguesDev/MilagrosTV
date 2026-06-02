<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentRequest;
use App\Models\Movie;
use App\Models\Serie;
use App\Models\Episode;
use App\Services\TmdbService;

class ContentRequestAdminController extends Controller
{
    public function __construct(private TmdbService $tmdb) {}

    public function index()
    {
        $requests = ContentRequest::with('user')
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'imported' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->get();

        $pendingCount = $requests->where('status', 'pending')->count();

        return view('admin.content-requests.index', compact('requests', 'pendingCount'));
    }

    public function import(ContentRequest $contentRequest)
    {
        $tmdbId = (int) $contentRequest->tmdb_id;

        if ($contentRequest->type === 'movie') {
            if (Movie::where('tmdb_id', $tmdbId)->exists()) {
                $contentRequest->update(['status' => 'imported']);
                return back()->with('success', '"{$contentRequest->title}" já estava importado.');
            }
            $data = $this->tmdb->getMovieDetails($tmdbId);
            Movie::create($this->tmdb->formatMovieData($data) + [
                'trailer_url'  => $this->tmdb->getTrailerUrl($tmdbId, 'movie'),
                'translations' => $this->tmdb->fetchTranslations($tmdbId, 'movie'),
            ]);
        } else {
            if (Serie::where('tmdb_id', $tmdbId)->exists()) {
                $contentRequest->update(['status' => 'imported']);
                return back()->with('success', '"{$contentRequest->title}" já estava importado.');
            }
            $data  = $this->tmdb->getSeriesDetails($tmdbId);
            $serie = Serie::create($this->tmdb->formatSeriesData($data) + [
                'trailer_url'  => $this->tmdb->getTrailerUrl($tmdbId, 'tv'),
                'translations' => $this->tmdb->fetchTranslations($tmdbId, 'tv'),
            ]);
            // Import all episodes
            foreach ($data['seasons'] ?? [] as $season) {
                $n = (int) $season['season_number'];
                if ($n < 1) continue;
                $eps = $this->tmdb->getSeasonEpisodes($tmdbId, $n);
                foreach ($eps as $ep) {
                    Episode::updateOrCreate(
                        ['serie_id' => $serie->id, 'season' => $n, 'episode' => $ep['episode_number']],
                        ['title' => $ep['name'] ?? null]
                    );
                }
            }
        }

        $contentRequest->update(['status' => 'imported']);

        return back()->with('success', "\"{$contentRequest->title}\" importado com sucesso.");
    }

    public function reject(ContentRequest $contentRequest)
    {
        $contentRequest->update(['status' => 'rejected']);
        return back()->with('success', 'Pedido rejeitado.');
    }
}
