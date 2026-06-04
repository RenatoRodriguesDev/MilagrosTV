<?php

namespace App\Http\Controllers;

use App\Models\MovieWatchProgress;
use App\Models\WatchedItem;
use App\Models\WatchProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show()
    {
        $user     = Auth::user();
        $progress = WatchProgress::where('user_id', $user->id)->get();
        $watched  = \App\Models\WatchedItem::where('user_id', $user->id)->get();

        // Genre breakdown from completed episodes
        $genreCount = [];
        WatchProgress::with(['episode.serie'])
            ->where('user_id', $user->id)
            ->where('position', '>', 60)
            ->get()
            ->each(function ($p) use (&$genreCount) {
                foreach ($p->episode?->serie?->localGenres() ?? [] as $g) {
                    $genreCount[$g] = ($genreCount[$g] ?? 0) + 1;
                }
            });
        arsort($genreCount);
        $topGenres = array_slice($genreCount, 0, 5, true);

        // Most watched series
        $topSeries = WatchProgress::with(['episode.serie'])
            ->where('user_id', $user->id)
            ->get()
            ->groupBy(fn($p) => $p->episode?->serie_id)
            ->map(fn($g) => ['serie' => $g->first()?->episode?->serie, 'count' => $g->count()])
            ->filter(fn($g) => $g['serie'])
            ->sortByDesc('count')
            ->take(5);

        $movieProgress = MovieWatchProgress::where('user_id', $user->id)->get();

        $stats = [
            'episodes_started'   => $progress->count(),
            'episodes_completed' => $progress->where('completed', true)->count(),
            'hours_watched'      => round(($progress->sum('position') + $movieProgress->sum('position')) / 3600, 1),
            'movies_watched'     => $movieProgress->count() ?: $watched->where('item_type', 'movie')->count(),
            'series_watched'     => $watched->where('item_type', 'serie')->count(),
        ];

        // Most watched movies
        $topMovies = MovieWatchProgress::with('movie')
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->limit(5)->get()
            ->filter(fn($p) => $p->movie)
            ->map(fn($p) => ['movie' => $p->movie, 'position' => $p->position]);

        // Recent activity — merge episodes + movies
        $recentEpisodes = WatchProgress::with(['episode.serie'])
            ->where('user_id', $user->id)->latest('updated_at')->limit(10)->get()
            ->map(fn($p) => [
                'type' => 'episode', 'updated_at' => $p->updated_at,
                'title' => $p->episode?->serie?->localTitle(),
                'subtitle' => 'T' . $p->episode?->season . 'E' . $p->episode?->episode . ($p->episode?->title ? ' · ' . $p->episode->title : ''),
                'poster' => $p->episode?->serie?->poster_url,
                'link' => $p->episode?->serie ? route('catalog.serie', $p->episode->serie) : null,
                'completed' => $p->completed, 'percent' => $p->percent, 'duration' => $p->duration,
            ]);

        $recentMovieProgress = MovieWatchProgress::with('movie')
            ->where('user_id', $user->id)->latest('updated_at')->limit(10)->get()
            ->map(fn($p) => [
                'type' => 'movie', 'updated_at' => $p->updated_at,
                'title' => $p->movie?->localTitle(),
                'subtitle' => $p->movie?->year,
                'poster' => $p->movie?->poster_url,
                'link' => $p->movie ? route('catalog.movie', $p->movie) : null,
                'completed' => $p->completed, 'percent' => $p->percent, 'duration' => $p->duration,
            ]);

        $recentProgress = $recentEpisodes->concat($recentMovieProgress)
            ->sortByDesc('updated_at')->take(5)->values();

        return view('profile.show', compact('user', 'stats', 'recentProgress', 'topGenres', 'topSeries', 'topMovies'));
    }

    public function history()
    {
        $user = Auth::user();

        // Merge episode progress + movie progress, sorted by updated_at
        $episodes = WatchProgress::with(['episode.serie'])
            ->where('user_id', $user->id)
            ->latest('updated_at')->get()
            ->map(fn($p) => [
                'type'       => 'episode',
                'title'      => $p->episode?->serie?->localTitle(),
                'subtitle'   => 'T' . $p->episode?->season . 'E' . $p->episode?->episode . ($p->episode?->title ? ' · ' . $p->episode->title : ''),
                'poster'     => $p->episode?->serie?->poster_url,
                'link'       => $p->episode?->serie ? route('catalog.serie', $p->episode->serie) : null,
                'completed'  => $p->completed,
                'percent'    => $p->percent,
                'position'   => $p->position,
                'duration'   => $p->duration,
                'updated_at' => $p->updated_at,
            ]);

        $movies = MovieWatchProgress::with('movie')
            ->where('user_id', $user->id)
            ->latest('updated_at')->get()
            ->map(fn($p) => [
                'type'       => 'movie',
                'title'      => $p->movie?->localTitle(),
                'subtitle'   => ($p->movie?->year ? $p->movie->year . ' · ' : '') . ($p->movie?->duration ? $p->movie->duration . ' min' : ''),
                'poster'     => $p->movie?->poster_url,
                'link'       => $p->movie ? route('catalog.movie', $p->movie) : null,
                'completed'  => $p->completed,
                'percent'    => $p->percent,
                'position'   => $p->position,
                'duration'   => $p->duration,
                'updated_at' => $p->updated_at,
            ]);

        $history = $episodes->concat($movies)
            ->sortByDesc('updated_at')
            ->values();

        // Manual pagination
        $page    = request()->input('page', 1);
        $perPage = 30;
        $paged   = new \Illuminate\Pagination\LengthAwarePaginator(
            $history->forPage($page, $perPage),
            $history->count(),
            $perPage,
            $page,
            ['path' => route('profile.history')]
        );

        return view('profile.history', ['history' => $paged]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->update($data);

        return back()->with('success', 'Perfil actualizado com sucesso.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:6|confirmed',
        ]);

        if (!Hash::check($request->current_password, Auth::user()->password)) {
            return back()->withErrors(['current_password' => 'Password actual incorrecta.']);
        }

        Auth::user()->update(['password' => bcrypt($request->password)]);

        return back()->with('success', 'Password alterada com sucesso.');
    }
}
