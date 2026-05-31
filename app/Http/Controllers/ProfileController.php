<?php

namespace App\Http\Controllers;

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

        $stats = [
            'episodes_started'   => $progress->count(),
            'episodes_completed' => $progress->where('completed', true)->count(),
            'hours_watched'      => round($progress->sum('position') / 3600, 1),
            'movies_watched'     => $watched->where('item_type', 'movie')->count(),
            'series_watched'     => $watched->where('item_type', 'serie')->count(),
        ];

        $recentProgress = WatchProgress::with(['episode.serie'])
            ->where('user_id', $user->id)
            ->latest('updated_at')
            ->limit(5)
            ->get();

        return view('profile.show', compact('user', 'stats', 'recentProgress', 'topGenres', 'topSeries'));
    }

    public function history()
    {
        $user = Auth::user();
        $history = WatchProgress::with(['episode.serie'])
            ->where('user_id', $user->id)
            ->latest('updated_at')
            ->paginate(30);

        return view('profile.history', compact('history'));
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
