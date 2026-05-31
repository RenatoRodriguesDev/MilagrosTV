<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Serie;
use App\Services\TmdbService;
use Illuminate\Http\Request;

class SerieController extends Controller
{
    public function __construct(private TmdbService $tmdb) {}

    public function index(Request $request)
    {
        $search = $request->input('search');
        $series = Serie::withCount([
                'episodes',
                'episodes as local_episodes_count' => fn($q) => $q->whereNotNull('video_path'),
            ])
            ->orderBy('title')
            ->when($search, fn($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('original_title', 'like', "%{$search}%"))
            ->get();
        return view('admin.series.index', compact('series', 'search'));
    }

    public function create()
    {
        return view('admin.series.create');
    }

    public function search(Request $request)
    {
        $request->validate(['query' => 'required|string|min:2']);
        $results = $this->tmdb->searchSeries($request->input('query'));
        return response()->json($results);
    }

    public function store(Request $request)
    {
        $request->validate(['title' => 'required|string|max:255']);

        $data = $request->only([
            'title', 'original_title', 'year', 'synopsis',
            'poster_url', 'tmdb_id', 'rating', 'seasons',
        ]);

        $data['genres'] = array_filter(array_map('trim', explode(',', $request->input('genres', ''))));

        if (!empty($data['tmdb_id'])) {
            $data['translations'] = $this->tmdb->fetchTranslations((int) $data['tmdb_id'], 'tv');
            $data['trailer_url']  = $this->tmdb->getTrailerUrl((int) $data['tmdb_id'], 'tv');
        }

        Serie::create($data);

        return redirect()->route('admin.series.index')->with('success', 'Série adicionada com sucesso!');
    }

    public function edit(Serie $serie)
    {
        $serie->load('episodes');
        return view('admin.series.edit', compact('serie'));
    }

    public function update(Request $request, Serie $serie)
    {
        $request->validate(['title' => 'required|string|max:255']);

        $data = $request->only([
            'title', 'original_title', 'year', 'synopsis',
            'poster_url', 'tmdb_id', 'rating', 'seasons',
        ]);

        $data['genres'] = array_filter(array_map('trim', explode(',', $request->input('genres', ''))));

        if (!empty($data['tmdb_id'])) {
            $data['translations'] = $this->tmdb->fetchTranslations((int) $data['tmdb_id'], 'tv');
            if (!$serie->trailer_url) {
                $data['trailer_url'] = $this->tmdb->getTrailerUrl((int) $data['tmdb_id'], 'tv');
            }
        }

        $serie->update($data);

        return redirect()->route('admin.series.index')->with('success', 'Série atualizada!');
    }

    public function destroy(Serie $serie)
    {
        $serie->delete();
        return redirect()->route('admin.series.index')->with('success', 'Série removida.');
    }

    public function tmdbDetails(Request $request)
    {
        $tmdbId = $request->input('tmdb_id');
        $data   = $this->tmdb->getSeriesDetails((int) $tmdbId);
        return response()->json($this->tmdb->formatSeriesData($data));
    }
}
