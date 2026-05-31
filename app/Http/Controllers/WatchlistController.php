<?php

namespace App\Http\Controllers;

use App\Models\Watchlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WatchlistController extends Controller
{
    public function index()
    {
        $items = Watchlist::where('user_id', Auth::id())
            ->latest()
            ->get();

        $movies = $items->where('item_type', 'movie')
            ->map(fn($i) => \App\Models\Movie::find($i->item_id))
            ->filter();

        $series = $items->where('item_type', 'serie')
            ->map(fn($i) => \App\Models\Serie::find($i->item_id))
            ->filter();

        return view('catalog.watchlist', compact('movies', 'series'));
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'item_type' => 'required|in:movie,serie',
            'item_id'   => 'required|integer',
        ]);

        $existing = Watchlist::where([
            'user_id'   => Auth::id(),
            'item_type' => $request->item_type,
            'item_id'   => $request->item_id,
        ])->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['in_watchlist' => false]);
        }

        Watchlist::create([
            'user_id'   => Auth::id(),
            'item_type' => $request->item_type,
            'item_id'   => $request->item_id,
        ]);

        return response()->json(['in_watchlist' => true]);
    }
}
