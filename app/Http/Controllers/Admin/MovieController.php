<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Services\TmdbService;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    public function __construct(private TmdbService $tmdb) {}

    public function index(Request $request)
    {
        $search = $request->input('search');
        $movies = Movie::orderBy('title')
            ->when($search, fn($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('original_title', 'like', "%{$search}%"))
            ->get();
        return view('admin.movies.index', compact('movies', 'search'));
    }

    public function create()
    {
        return view('admin.movies.create');
    }

    public function search(Request $request)
    {
        $request->validate(['query' => 'required|string|min:2']);
        $results = $this->tmdb->searchMovie($request->input('query'));
        return response()->json($results);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $data = $request->only([
            'title', 'original_title', 'year', 'synopsis',
            'poster_url', 'video_path', 'tmdb_id', 'rating', 'duration', 'piratahub_url', 'cinemacity_id',
        ]);

        $data['genres'] = array_filter(array_map('trim', explode(',', $request->input('genres', ''))));

        if (!empty($data['tmdb_id'])) {
            $existing = Movie::where('tmdb_id', $data['tmdb_id'])->first();
            if ($existing) {
                return redirect()->route('admin.movies.edit', $existing)
                    ->with('warning', 'Este filme já existe — redireccionado para edição.');
            }
            $data['translations'] = $this->tmdb->fetchTranslations((int) $data['tmdb_id'], 'movie');
            $data['trailer_url']  = $this->tmdb->getTrailerUrl((int) $data['tmdb_id'], 'movie');
        }

        Movie::create($data);

        \Illuminate\Support\Facades\Cache::flush();
        return redirect()->route('admin.movies.index')->with('success', 'Filme adicionado com sucesso!');
    }

    public function edit(Movie $movie)
    {
        return view('admin.movies.edit', compact('movie'));
    }

    public function update(Request $request, Movie $movie)
    {
        $request->validate(['title' => 'required|string|max:255']);

        $data = $request->only([
            'title', 'original_title', 'year', 'synopsis',
            'poster_url', 'video_path', 'tmdb_id', 'rating', 'duration', 'piratahub_url', 'cinemacity_id',
        ]);

        $data['genres'] = array_filter(array_map('trim', explode(',', $request->input('genres', ''))));

        if (!empty($data['tmdb_id'])) {
            $data['translations'] = $this->tmdb->fetchTranslations((int) $data['tmdb_id'], 'movie');
            if (!$movie->trailer_url) {
                $data['trailer_url'] = $this->tmdb->getTrailerUrl((int) $data['tmdb_id'], 'movie');
            }
        }

        $movie->update($data);

        return redirect()->route('admin.movies.index')->with('success', 'Filme atualizado!');
    }

    public function destroy(Movie $movie)
    {
        $movie->delete();
        return redirect()->route('admin.movies.index')->with('success', 'Filme removido.');
    }

    public function tmdbDetails(Request $request)
    {
        $tmdbId = $request->input('tmdb_id');
        $data   = $this->tmdb->getMovieDetails((int) $tmdbId);
        return response()->json($this->tmdb->formatMovieData($data));
    }
}
