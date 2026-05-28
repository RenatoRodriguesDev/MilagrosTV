<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\Movie;
use App\Models\Serie;
use App\Models\User;
use App\Models\WatchProgress;

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
        ];

        $recentMovies  = Movie::latest()->limit(5)->get();
        $recentSeries  = Serie::with('episodes')->latest()->limit(5)->get();
        $recentProgress = WatchProgress::with(['user', 'episode'])
            ->latest('updated_at')
            ->limit(8)
            ->get();
        $users = User::withCount('watchProgress')->latest()->limit(6)->get();

        return view('admin.dashboard', compact(
            'stats', 'recentMovies', 'recentSeries', 'recentProgress', 'users'
        ));
    }
}
