<?php

namespace App\Http\Controllers;

use App\Models\ContentRequest;
use App\Services\TmdbService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContentRequestController extends Controller
{
    public function tmdbSearch(Request $request, TmdbService $tmdb)
    {
        $query = trim($request->input('query', ''));
        if (strlen($query) < 2) return response()->json([]);

        $movies = collect($tmdb->searchMovie($query))->map(fn($m) => [
            'id'             => $m['id'],
            'tmdb_id'        => $m['id'],
            'type'           => 'movie',
            'title'          => $m['title'] ?? $m['original_title'] ?? '',
            'original_title' => $m['original_title'] ?? null,
            'poster_url'     => isset($m['poster_path']) ? 'https://image.tmdb.org/t/p/w92' . $m['poster_path'] : null,
            'year'           => substr($m['release_date'] ?? '', 0, 4) ?: null,
            'popularity'     => $m['popularity'] ?? 0,
        ]);

        $series = collect($tmdb->searchSeries($query))->map(fn($s) => [
            'id'             => $s['id'],
            'tmdb_id'        => $s['id'],
            'type'           => 'tv',
            'title'          => $s['name'] ?? $s['original_name'] ?? '',
            'original_title' => $s['original_name'] ?? null,
            'poster_url'     => isset($s['poster_path']) ? 'https://image.tmdb.org/t/p/w92' . $s['poster_path'] : null,
            'year'           => substr($s['first_air_date'] ?? '', 0, 4) ?: null,
            'popularity'     => $s['popularity'] ?? 0,
        ]);

        return response()->json(
            $movies->concat($series)->sortByDesc('popularity')->take(8)->values()
        );
    }

    public function checkCatalog(Request $request)
    {
        $items  = $request->input('items', []); // [{tmdb_id, type}, ...]
        $result = [];

        foreach ($items as $item) {
            $tmdbId = (int) ($item['tmdb_id'] ?? 0);
            $type   = $item['type'] ?? '';
            if (!$tmdbId) continue;

            $inCatalog = $type === 'movie'
                ? \App\Models\Movie::where('tmdb_id', $tmdbId)->exists()
                : \App\Models\Serie::where('tmdb_id', $tmdbId)->exists();

            $result[$tmdbId . '_' . $type] = $inCatalog;
        }

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tmdb_id'        => 'required|string',
            'type'           => 'required|in:movie,tv',
            'title'          => 'required|string|max:255',
            'original_title' => 'nullable|string|max:255',
            'poster_url'     => 'nullable|url',
            'year'           => 'nullable|integer',
        ]);

        $existing = ContentRequest::where([
            'user_id' => Auth::id(),
            'tmdb_id' => $data['tmdb_id'],
            'type'    => $data['type'],
        ])->first();

        if ($existing) {
            return response()->json([
                'ok'      => false,
                'message' => __('catalog.request_already_sent'),
                'status'  => $existing->status,
            ]);
        }

        ContentRequest::create($data + ['user_id' => Auth::id()]);

        return response()->json(['ok' => true, 'message' => __('catalog.request_sent')]);
    }
}
