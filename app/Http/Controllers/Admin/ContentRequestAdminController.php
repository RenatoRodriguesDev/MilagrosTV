<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PushController;
use App\Models\ContentRequest;
use App\Models\Movie;
use App\Models\Serie;
use App\Models\Episode;
use App\Models\UserNotification;
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
            $existingMovie = Movie::where('tmdb_id', $tmdbId)->first();
            if ($existingMovie) {
                $contentRequest->update(['status' => 'imported']);
                return back()->with('warning', "\"{$contentRequest->title}\" já existe no catálogo.");
            }
            $data = $this->tmdb->getMovieDetails($tmdbId);
            Movie::create($this->tmdb->formatMovieData($data) + [
                'trailer_url'  => $this->tmdb->getTrailerUrl($tmdbId, 'movie'),
                'translations' => $this->tmdb->fetchTranslations($tmdbId, 'movie'),
            ]);
        } else {
            $existingSerie = Serie::where('tmdb_id', $tmdbId)->first();
            if ($existingSerie) {
                $contentRequest->update(['status' => 'imported']);
                return back()->with('warning', "\"{$contentRequest->title}\" já existe no catálogo.");
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

        // Notify the user who requested the content
        $title   = $contentRequest->title;
        $userId  = $contentRequest->user_id;
        $notifUrl = route($contentRequest->type === 'movie' ? 'catalog.index' : 'catalog.index', ['type' => $contentRequest->type === 'movie' ? 'movies' : 'series']);

        UserNotification::create([
            'user_id' => $userId,
            'type'    => 'content_available',
            'title'   => "\"$title\" está disponível!",
            'message' => "O conteúdo que pediste foi adicionado ao catálogo.",
            'url'     => $notifUrl,
            'read'    => false,
        ]);

        PushController::sendToUser($userId, "✅ \"$title\" disponível", "O conteúdo que pediste foi adicionado ao catálogo.", $notifUrl);

        return back()->with('success', "\"{$title}\" importado com sucesso.");
    }

    public function reject(ContentRequest $contentRequest)
    {
        $contentRequest->update(['status' => 'rejected']);

        $title  = $contentRequest->title;
        $userId = $contentRequest->user_id;

        UserNotification::create([
            'user_id' => $userId,
            'type'    => 'content_rejected',
            'title'   => "Pedido rejeitado",
            'message' => "O teu pedido de \"$title\" não foi aceite.",
            'url'     => null,
            'read'    => false,
        ]);

        PushController::sendToUser($userId, "❌ Pedido rejeitado", "O teu pedido de \"$title\" não foi aceite.", null);

        return back()->with('success', 'Pedido rejeitado.');
    }
}
