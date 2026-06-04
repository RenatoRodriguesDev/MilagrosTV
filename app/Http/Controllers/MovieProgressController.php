<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\MovieWatchProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MovieProgressController extends Controller
{
    public function show(Movie $movie)
    {
        $p = MovieWatchProgress::where(['user_id' => Auth::id(), 'movie_id' => $movie->id])->first();
        return response()->json($p ? [
            'position'  => $p->position,
            'duration'  => $p->duration,
            'completed' => $p->completed,
            'percent'   => $p->percent,
        ] : ['position' => 0, 'duration' => 0, 'completed' => false, 'percent' => 0]);
    }

    public function store(Request $request, Movie $movie)
    {
        $data = $request->validate([
            'position'  => 'required|integer|min:0',
            'duration'  => 'required|integer|min:0',
            'completed' => 'boolean',
        ]);

        MovieWatchProgress::updateOrCreate(
            ['user_id' => Auth::id(), 'movie_id' => $movie->id],
            $data
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Movie $movie)
    {
        MovieWatchProgress::where(['user_id' => Auth::id(), 'movie_id' => $movie->id])->delete();
        return response()->json(['ok' => true]);
    }
}
