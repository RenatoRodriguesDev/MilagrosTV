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
        $user = Auth::user();

        $progress = WatchProgress::where('user_id', $user->id)->get();
        $watched  = WatchedItem::where('user_id', $user->id)->get();

        $stats = [
            'episodes_started'   => $progress->count(),
            'episodes_completed' => $progress->where('completed', true)->count(),
            'hours_watched'      => round($progress->sum('position') / 3600, 1),
            'movies_watched'     => $watched->where('item_type', 'movie')->count(),
            'series_watched'     => $watched->where('item_type', 'serie')->count(),
        ];

        // Genre breakdown from watch history
        $recentProgress = WatchProgress::with(['episode.serie'])
            ->where('user_id', $user->id)
            ->latest('updated_at')
            ->limit(5)
            ->get();

        return view('profile.show', compact('user', 'stats', 'recentProgress'));
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
