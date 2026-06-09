<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Serie;
use App\Services\CinemaCityService;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CinemaCityController extends Controller
{
    public function __construct(private CinemaCityService $service) {}

    public function episode(Serie $serie, int $season, int $episode): JsonResponse
    {
        set_time_limit(120);

        if (!$serie->cinemacity_id) {
            return response()->json(['error' => 'not_configured'], 404);
        }

        $url = $this->service->getEpisodeUrl($serie->cinemacity_id, $season, $episode);

        if (!$url) {
            return response()->json(['error' => 'not_found'], 404);
        }

        return response()->json(['url' => $this->proxyUrl($url)]);
    }

    public function movie(Movie $movie): JsonResponse
    {
        set_time_limit(120);

        if (!$movie->cinemacity_id) {
            return response()->json(['error' => 'not_configured'], 404);
        }

        $url = $this->service->getMovieUrl($movie->cinemacity_id);

        if (!$url) {
            return response()->json(['error' => 'not_found'], 404);
        }

        return response()->json(['url' => $this->proxyUrl($url)]);
    }

    public function hlsProxy(Request $request): Response
    {
        $url = $request->query('url');

        if (!$url || !str_contains(parse_url($url, PHP_URL_HOST) ?? '', 'cccdn.net')) {
            abort(403);
        }

        try {
            $res  = (new Client(['verify' => false]))->get($url, ['timeout' => 30]);
        } catch (\Throwable) {
            abort(502);
        }

        $type = $res->getHeaderLine('Content-Type') ?: 'application/octet-stream';
        $body = (string) $res->getBody();

        if (str_contains($type, 'mpegurl') || str_ends_with(parse_url($url, PHP_URL_PATH) ?? '', '.m3u8')) {
            $base = substr($url, 0, strrpos($url, '/') + 1);

            // Rewrite bare segment/playlist lines
            $body = preg_replace_callback('/^(?!#)(\S+)$/m', function ($m) use ($base) {
                $seg = str_starts_with($m[1], 'http') ? $m[1] : $base . $m[1];
                return '/cinemacity/hls-proxy?url=' . urlencode($seg);
            }, $body);

            // Rewrite URI="..." in EXT-X-MEDIA / EXT-X-KEY tags
            $body = preg_replace_callback('/URI="([^"]+)"/', function ($m) use ($base) {
                $seg = str_starts_with($m[1], 'http') ? $m[1] : $base . $m[1];
                return 'URI="/cinemacity/hls-proxy?url=' . urlencode($seg) . '"';
            }, $body);

            $type = 'application/vnd.apple.mpegurl';
        }

        return response($body, 200, [
            'Content-Type'                => $type,
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control'               => 'no-cache',
        ]);
    }

    private function proxyUrl(string $cdnUrl): string
    {
        return '/cinemacity/hls-proxy?url=' . urlencode($cdnUrl);
    }
}
