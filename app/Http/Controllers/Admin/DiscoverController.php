<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\Serie;
use App\Services\TmdbService;
use Illuminate\Http\Request;

class DiscoverController extends Controller
{
    public function __construct(private TmdbService $tmdb) {}

    public function index(Request $request)
    {
        $type     = $request->input('type', 'movie');
        $category = $request->input('category', 'popular');
        $page     = max(1, (int) $request->input('page', 1));

        $data    = $this->tmdb->discover($type, $category, $page);
        $results = $data['results'];

        // Mark which are already in the DB
        $existingTmdbIds = $type === 'movie'
            ? Movie::whereNotNull('tmdb_id')->pluck('tmdb_id')->toArray()
            : Serie::whereNotNull('tmdb_id')->pluck('tmdb_id')->toArray();

        $results = array_map(function ($item) use ($existingTmdbIds, $type) {
            $item['already_imported'] = in_array($item['id'], $existingTmdbIds);
            $item['_type'] = $type;
            return $item;
        }, $results);

        return view('admin.discover', [
            'results'     => $results,
            'type'        => $type,
            'category'    => $category,
            'page'        => $page,
            'totalPages'  => min($data['total_pages'], 20),
            'totalResults'=> $data['total_results'],
        ]);
    }

    public function import(Request $request)
    {
        $tmdbId = (int) $request->input('tmdb_id');
        $type   = $request->input('type');

        if ($type === 'movie') {
            if (Movie::where('tmdb_id', $tmdbId)->exists()) {
                return response()->json(['error' => 'Já importado.'], 409);
            }
            $data  = $this->tmdb->getMovieDetails($tmdbId);
            $movie = Movie::create($this->tmdb->formatMovieData($data) + [
                'trailer_url'  => $this->tmdb->getTrailerUrl($tmdbId, 'movie'),
                'translations' => $this->tmdb->fetchTranslations($tmdbId, 'movie'),
            ]);
            return response()->json(['ok' => true, 'id' => $movie->id, 'title' => $movie->title]);
        }

        if ($type === 'tv') {
            if (Serie::where('tmdb_id', $tmdbId)->exists()) {
                return response()->json(['error' => 'Já importado.'], 409);
            }
            $data  = $this->tmdb->getSeriesDetails($tmdbId);
            $serie = Serie::create($this->tmdb->formatSeriesData($data) + [
                'trailer_url'  => $this->tmdb->getTrailerUrl($tmdbId, 'tv'),
                'translations' => $this->tmdb->fetchTranslations($tmdbId, 'tv'),
            ]);
            return response()->json(['ok' => true, 'id' => $serie->id, 'title' => $serie->title]);
        }

        return response()->json(['error' => 'Tipo inválido.'], 400);
    }
}
