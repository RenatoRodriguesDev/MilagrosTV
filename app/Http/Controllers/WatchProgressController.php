<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\WatchProgress;
use Illuminate\Http\Request;

class WatchProgressController extends Controller
{
    public function show(Episode $episode)
    {
        $progress = WatchProgress::where('user_id', auth()->id())
            ->where('episode_id', $episode->id)
            ->first();

        return response()->json($progress
            ? ['position' => $progress->position, 'duration' => $progress->duration, 'completed' => $progress->completed]
            : ['position' => 0, 'duration' => 0, 'completed' => false]
        );
    }

    public function destroy(Episode $episode)
    {
        WatchProgress::where('user_id', auth()->id())
            ->where('episode_id', $episode->id)
            ->delete();
        return response()->json(['ok' => true]);
    }

    public function store(Request $request, Episode $episode)
    {
        $data = $request->validate([
            'position'  => 'required|integer|min:0',
            'duration'  => 'nullable|integer|min:0',
            'completed' => 'nullable|boolean',
        ]);

        WatchProgress::updateOrCreate(
            ['user_id' => auth()->id(), 'episode_id' => $episode->id],
            [
                'position'  => $data['position'],
                'duration'  => $data['duration'] ?? 0,
                'completed' => $data['completed'] ?? false,
            ]
        );

        return response()->json(['ok' => true]);
    }
}
