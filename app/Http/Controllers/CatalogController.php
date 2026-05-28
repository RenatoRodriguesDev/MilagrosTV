<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\Movie;
use App\Models\Serie;
use App\Models\WatchedItem;
use App\Models\WatchProgress;
use App\Models\Watchlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $search  = $request->input('search');
        $genre   = $request->input('genre');
        $type    = $request->input('type', 'all');
        $sort    = $request->input('sort', 'title'); // title, year, rating, added
        $order   = $request->input('order', 'asc');

        $movies = ($type === 'series') ? collect() : Movie::query();
        $series = ($type === 'movies') ? collect() : Serie::query();

        if (!($type === 'series')) {
            if ($search) $movies->where(fn($q) => $q->where('title', 'like', "%{$search}%")->orWhere('original_title', 'like', "%{$search}%"));
            if ($genre)  $movies->whereJsonContains('genres', $genre);
            $movies = $this->applySortEloquent($movies, $sort, $order)->get();
        }

        if (!($type === 'movies')) {
            if ($search) $series->where(fn($q) => $q->where('title', 'like', "%{$search}%")->orWhere('original_title', 'like', "%{$search}%"));
            if ($genre)  $series->whereJsonContains('genres', $genre);
            $series = $this->applySortEloquent($series, $sort, $order)->get();
        }

        // Locale-based sort for title (SQLite doesn't do locale sorting)
        if ($sort === 'title') {
            $locale = app()->getLocale();
            if ($movies instanceof \Illuminate\Support\Collection) {
                $movies = $movies->sortBy(fn($m) => mb_strtolower($m->localTitle()));
            }
            if ($series instanceof \Illuminate\Support\Collection) {
                $series = $series->sortBy(fn($s) => mb_strtolower($s->localTitle()));
            }
        }

        $watchedIds   = $this->getWatchedIds();
        $watchlistIds = $this->getWatchlistIds();
        $allGenres    = $this->getAllGenres();
        $continueWatching = $this->getContinueWatching();

        return view('catalog.index', compact(
            'movies', 'series', 'watchedIds', 'watchlistIds', 'allGenres',
            'search', 'genre', 'type', 'sort', 'order', 'continueWatching'
        ));
    }

    public function serie(Serie $serie)
    {
        $episodes = $serie->episodes()->get()->groupBy('season');
        $progress = [];

        if (Auth::check()) {
            $episodeIds = $serie->episodes()->pluck('id');
            $progress = WatchProgress::where('user_id', Auth::id())
                ->whereIn('episode_id', $episodeIds)
                ->get()
                ->keyBy('episode_id');
        }

        $inWatchlist = Auth::check() && Watchlist::where([
            'user_id'   => Auth::id(),
            'item_type' => 'serie',
            'item_id'   => $serie->id,
        ])->exists();

        return view('catalog.serie', compact('serie', 'episodes', 'progress', 'inWatchlist'));
    }

    public function movie(Movie $movie)
    {
        $inWatchlist = Auth::check() && Watchlist::where([
            'user_id'   => Auth::id(),
            'item_type' => 'movie',
            'item_id'   => $movie->id,
        ])->exists();

        return view('catalog.movie', compact('movie', 'inWatchlist'));
    }

    public function toggleWatched(Request $request)
    {
        $request->validate([
            'item_type' => 'required|in:movie,serie',
            'item_id'   => 'required|integer',
        ]);

        $type = $request->input('item_type');
        $id   = $request->input('item_id');

        $existing = WatchedItem::where('user_id', Auth::id())
            ->where('item_type', $type)
            ->where('item_id', $id)
            ->first();

        if ($existing) {
            $existing->delete();
            $watched = false;
        } else {
            WatchedItem::create(['user_id' => Auth::id(), 'item_type' => $type, 'item_id' => $id]);
            $watched = true;
        }

        return response()->json(['watched' => $watched]);
    }

    private function getContinueWatching(): \Illuminate\Support\Collection
    {
        return WatchProgress::with(['episode.serie'])
            ->where('user_id', Auth::id())
            ->where('completed', false)
            ->where('position', '>', 30)
            ->where('duration', '>', 0)
            ->latest('updated_at')
            ->limit(10)
            ->get()
            ->filter(fn($p) => $p->episode && $p->episode->serie);
    }

    private function getWatchedIds(): array
    {
        return WatchedItem::where('user_id', Auth::id())
            ->get()
            ->groupBy('item_type')
            ->map(fn($items) => $items->pluck('item_id')->toArray())
            ->toArray();
    }

    private function getWatchlistIds(): array
    {
        return Watchlist::where('user_id', Auth::id())
            ->get()
            ->groupBy('item_type')
            ->map(fn($items) => $items->pluck('item_id')->toArray())
            ->toArray();
    }

    private function getAllGenres(): array
    {
        return Movie::whereNotNull('genres')->get()
            ->merge(Serie::whereNotNull('genres')->get())
            ->flatMap(fn($item) => $item->localGenres())
            ->filter()->unique()->sort()->values()->toArray();
    }

    private function applySortEloquent($query, string $sort, string $order)
    {
        return match($sort) {
            'year'   => $query->orderBy('year', $order),
            'rating' => $query->orderBy('rating', $order === 'asc' ? 'asc' : 'desc'),
            'added'  => $query->orderBy('created_at', $order),
            default  => $query->orderBy('title', 'asc'),
        };
    }
}
