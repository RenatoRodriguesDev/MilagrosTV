<?php

namespace App\Http\Controllers;

use App\Services\JackettService;
use Illuminate\Http\Request;

class TorrentController extends Controller
{
    public function __construct(private JackettService $jackett) {}

    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'type'  => 'nullable|in:movie,series,all',
        ]);

        $results = $this->jackett->search(
            $request->input('query'),
            $request->input('type', 'all')
        );

        return response()->json($results);
    }
}
