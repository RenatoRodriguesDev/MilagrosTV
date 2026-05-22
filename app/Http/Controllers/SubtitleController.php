<?php

namespace App\Http\Controllers;

use App\Services\SubdlService;
use Illuminate\Http\Request;

class SubtitleController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'query'    => 'required|string|max:200',
            'lang'     => 'nullable|string|max:20',
            'type'     => 'nullable|in:movie,tv',
            'season'   => 'nullable|integer',
            'episode'  => 'nullable|integer',
        ]);

        $langs   = strtoupper($request->input('lang', 'PT,EN,ES'));
        $service = app(SubdlService::class);

        $results = $service->search(
            query:   $request->input('query'),
            languages: $langs,
            type:    $request->input('type'),
            season:  $request->input('season'),
            episode: $request->input('episode'),
        );

        return response()->json($results);
    }

    public function download(Request $request)
    {
        $request->validate(['url' => 'required|string']);

        $url = $request->input('url');

        // Só permite URLs do Subdl
        if (!str_starts_with($url, '/subtitle/')) {
            return response('URL inválido.', 400);
        }

        try {
            $service = app(SubdlService::class);
            $vtt     = $service->downloadVtt($url);

            return response($vtt, 200, [
                'Content-Type'                => 'text/vtt; charset=utf-8',
                'Access-Control-Allow-Origin' => '*',
            ]);
        } catch (\Exception $e) {
            return response($e->getMessage(), 500);
        }
    }
}
