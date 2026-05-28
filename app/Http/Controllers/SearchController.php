<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\Movie;
use App\Models\Serie;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $q = trim($request->input('q', ''));
        if (strlen($q) < 2) return response()->json([]);

        $like = "%{$q}%";

        $movies = Movie::where('title', 'like', $like)
            ->orWhere('original_title', 'like', $like)
            ->limit(5)->get()->map(fn($m) => [
                'type'   => 'movie',
                'id'     => $m->id,
                'title'  => $m->localTitle(),
                'year'   => $m->year,
                'poster' => $m->poster_url,
                'url'    => route('catalog.movie', $m),
            ]);

        $series = Serie::where('title', 'like', $like)
            ->orWhere('original_title', 'like', $like)
            ->limit(5)->get()->map(fn($s) => [
                'type'   => 'serie',
                'id'     => $s->id,
                'title'  => $s->localTitle(),
                'year'   => $s->year,
                'poster' => $s->poster_url,
                'url'    => route('catalog.serie', $s),
            ]);

        $episodes = Episode::where('title', 'like', $like)
            ->with('serie')
            ->limit(4)->get()->map(fn($e) => [
                'type'   => 'episode',
                'id'     => $e->id,
                'title'  => ($e->serie->localTitle() ?? '') . ' — ' . ($e->title ?: "T{$e->season}E{$e->episode}"),
                'year'   => null,
                'poster' => $e->serie->poster_url ?? null,
                'url'    => route('catalog.serie', $e->serie_id) . '#ep-' . $e->id,
            ]);

        return response()->json([
            ...$movies->toArray(),
            ...$series->toArray(),
            ...$episodes->toArray(),
        ]);
    }
}
