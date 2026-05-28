<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\Movie;
use App\Models\Serie;
use App\Models\User;
use App\Models\WatchProgress;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'movies'         => Movie::count(),
            'series'         => Serie::count(),
            'episodes'       => Episode::count(),
            'local_episodes' => Episode::whereNotNull('video_path')->count(),
            'users'          => User::count(),
            'views_today'    => WatchProgress::whereDate('updated_at', today())->count(),
        ];

        $recentMovies   = Movie::latest()->limit(5)->get();
        $recentSeries   = Serie::with('episodes')->latest()->limit(5)->get();
        $recentProgress = WatchProgress::with(['user', 'episode.serie'])
            ->latest('updated_at')->limit(8)->get();
        $users = User::withCount('watchProgress')->latest()->limit(6)->get();

        // Views per day (last 14 days)
        $viewsPerDay = WatchProgress::select(
                DB::raw("date(updated_at) as date"),
                DB::raw("count(*) as total")
            )
            ->where('updated_at', '>=', now()->subDays(13))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        $days = collect();
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $days[$date] = $viewsPerDay[$date] ?? 0;
        }

        // Most watched episodes
        $topEpisodes = WatchProgress::with(['episode.serie'])
            ->select('episode_id', DB::raw('count(*) as views'))
            ->groupBy('episode_id')
            ->orderByDesc('views')
            ->limit(5)
            ->get();

        // Most active users
        $topUsers = User::withCount('watchProgress')
            ->orderByDesc('watch_progress_count')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'stats', 'recentMovies', 'recentSeries', 'recentProgress',
            'users', 'days', 'topEpisodes', 'topUsers'
        ));
    }
}
