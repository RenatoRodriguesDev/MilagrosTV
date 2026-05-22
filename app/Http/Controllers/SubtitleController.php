<?php

namespace App\Http\Controllers;

use App\Services\OpenSubtitlesService;
use Illuminate\Http\Request;

class SubtitleController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'query'   => 'required|string|max:200',
            'type'    => 'nullable|in:movie,tv',
            'season'  => 'nullable|integer',
            'episode' => 'nullable|integer',
        ]);

        $season  = $request->integer('season') ?: null;
        $episode = $request->integer('episode') ?: null;

        $results = app(OpenSubtitlesService::class)->search(
            query:   $request->input('query'),
            type:    $request->input('type'),
            season:  $season,
            episode: $episode,
        );

        // Filter to exact episode only when episode is specified
        if ($episode) {
            $results = array_values(array_filter($results, function ($r) use ($episode) {
                $from = $r['episode_from'] ?? null;
                $end  = $r['episode_end']  ?? null;
                if (!$from) return false;
                $from = (int) $from;
                $end  = $end !== null ? (int) $end : $from;
                return $from === $episode && $end === $episode;
            }));
        }

        return response()->json($results);
    }

    public function download(Request $request)
    {
        $request->validate(['file_id' => 'required|integer']);

        try {
            $vtt = app(OpenSubtitlesService::class)->downloadVtt((int) $request->input('file_id'));

            return response($vtt, 200, [
                'Content-Type'                => 'text/vtt; charset=utf-8',
                'Access-Control-Allow-Origin' => '*',
            ]);
        } catch (\Exception $e) {
            return response($e->getMessage(), 500);
        }
    }
}
