<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\Movie;
use App\Models\Serie;
use App\Models\WatchedItem;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $genre  = $request->input('genre');
        $type   = $request->input('type', 'all');

        $movies = ($type === 'series') ? collect() : Movie::orderBy('title')->get();
        $series = ($type === 'movies') ? collect() : Serie::orderBy('title')->get();

        if ($search) {
            $q = mb_strtolower($search);
            $movies = $movies->filter(fn($m) => str_contains(mb_strtolower($m->localTitle()), $q));
            $series = $series->filter(fn($s) => str_contains(mb_strtolower($s->localTitle()), $q));
        }

        if ($genre) {
            $movies = $movies->filter(fn($m) => in_array($genre, $m->localGenres()));
            $series = $series->filter(fn($s) => in_array($genre, $s->localGenres()));
        }

        $watchedIds = $this->getWatchedIds($request);
        $allGenres  = $this->getAllGenres();

        return view('catalog.index', compact('movies', 'series', 'watchedIds', 'allGenres', 'search', 'genre', 'type'));
    }

    public function serie(Serie $serie)
    {
        $episodes = $serie->episodes()->get()->groupBy('season');
        return view('catalog.serie', compact('serie', 'episodes'));
    }

    public function movie(Movie $movie)
    {
        return view('catalog.movie', compact('movie'));
    }

    public function toggleWatched(Request $request)
    {
        $request->validate([
            'item_type' => 'required|in:movie,serie',
            'item_id'   => 'required|integer',
        ]);

        $sessionId = $request->session()->getId();
        $type      = $request->input('item_type');
        $id        = $request->input('item_id');

        $existing = WatchedItem::where([
            'session_id' => $sessionId,
            'item_type'  => $type,
            'item_id'    => $id,
        ])->first();

        if ($existing) {
            $existing->delete();
            $watched = false;
        } else {
            WatchedItem::create([
                'session_id' => $sessionId,
                'item_type'  => $type,
                'item_id'    => $id,
            ]);
            $watched = true;
        }

        return response()->json(['watched' => $watched]);
    }

    private function getWatchedIds(Request $request): array
    {
        $sessionId = $request->session()->getId();
        return WatchedItem::where('session_id', $sessionId)
            ->get()
            ->groupBy('item_type')
            ->map(fn($items) => $items->pluck('item_id')->toArray())
            ->toArray();
    }

    private function getAllGenres(): array
    {
        $movies = Movie::whereNotNull('genres')->get();
        $series = Serie::whereNotNull('genres')->get();

        return $movies->merge($series)
            ->flatMap(fn($item) => $item->localGenres())
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }
}
