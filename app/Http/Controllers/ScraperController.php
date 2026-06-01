<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ScraperController extends Controller
{
    private array $headers = [
        'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language' => 'es-ES,es;q=0.9,en;q=0.8',
        'Accept-Encoding' => 'gzip, deflate, br',
        'Cache-Control'   => 'no-cache',
    ];

    // Try multiple piratahub.to URL patterns and return embed URL from the first that works
    public function find(Request $request)
    {
        $slug    = trim($request->input('slug', ''));
        $season  = (int) $request->input('season', 1);
        $episode = (int) $request->input('episode', 1);

        abort_unless($slug, 400);

        $candidates = [
            "https://piratahub.to/{$slug}-temporada-{$season}/capitulo-{$episode}/",
            "https://piratahub.to/{$slug}/capitulo-{$episode}/",
            "https://piratahub.to/{$slug}/temporada-{$season}-capitulo-{$episode}/",
            "https://piratahub.to/{$slug}-temporada-{$season}/capitulo-{$episode}-2/", // some sites add -2 suffix
        ];

        // Season 1 sometimes has no season suffix at all — already covered above.
        // For season > 1 remove bare /capitulo-N/ pattern (would match season 1 episodes).
        if ($season > 1) {
            $candidates = array_filter($candidates, fn($u) => str_contains($u, "temporada-{$season}"));
        }

        foreach ($candidates as $url) {
            try {
                $response = Http::withHeaders($this->headers + ['Referer' => 'https://piratahub.to/'])->timeout(12)->get($url);
                if (!$response->successful()) continue;

                $result = $this->extractEmbed($response->body(), parse_url($url, PHP_URL_HOST));
                if ($result) return response()->json($result);
            } catch (\Throwable) {
                continue;
            }
        }

        return response()->json(['error' => 'Episódio não encontrado no piratahub.to', 'slug' => $slug, 'season' => $season, 'episode' => $episode], 404);
    }

    // Scrape a known URL directly
    public function extract(Request $request)
    {
        $url = $request->input('url');
        abort_unless($url && filter_var($url, FILTER_VALIDATE_URL), 400);

        $host = parse_url($url, PHP_URL_HOST);

        try {
            $response = Http::withHeaders($this->headers + ['Referer' => "https://{$host}/"])->timeout(15)->get($url);

            if (!$response->successful()) {
                return response()->json(['error' => 'Site returned ' . $response->status(), 'status' => $response->status()], 422);
            }

            $result = $this->extractEmbed($response->body(), $host);
            if ($result) return response()->json($result);

            return response()->json(['error' => 'No player found — JS-loaded', 'html_len' => strlen($response->body())], 404);

        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }
    }

    private function extractEmbed(string $html, string $host): ?array
    {
        // 1. minochinos/vidhide /embed/ URL anywhere in the HTML
        if (preg_match('/["\']([^"\']*(?:vidhide|minochinos)[^"\']*\/embed\/[^"\']+)["\']/', $html, $m)) {
            return ['type' => 'iframe', 'url' => $m[1], 'source' => 'vidhide'];
        }

        // 2. Any embed/player iframe src attribute
        if (preg_match('/src=["\']([^"\']*\/(?:embed|e|player)\/[^"\']+)["\']/', $html, $m)) {
            return ['type' => 'iframe', 'url' => $m[1], 'source' => 'embed'];
        }

        // 3. HLS .m3u8 URL
        if (preg_match('/["\']([^"\']*\.m3u8[^"\']*)["\']/', $html, $m)) {
            return ['type' => 'hls', 'url' => $m[1], 'source' => 'direct'];
        }

        // 4. WordPress REST API fallback
        if (preg_match('/post[_-]?id["\s:=]+(\d+)/', $html, $pid)) {
            try {
                $wp      = Http::timeout(8)->get("https://{$host}/wp-json/wp/v2/posts/{$pid[1]}")->json();
                $content = $wp['content']['rendered'] ?? '';
                if (preg_match('/src=["\']([^"\']*(?:vidhide|minochinos)[^"\']*)["\']/', $content, $wm)) {
                    return ['type' => 'iframe', 'url' => $wm[1], 'source' => 'wp-api'];
                }
            } catch (\Throwable) {}
        }

        return null;
    }
}
