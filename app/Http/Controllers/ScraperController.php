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

    // Find a movie on piratahub.to
    public function findMovie(Request $request)
    {
        $slug    = trim($request->input('slug', ''));
        $esSlug  = trim($request->input('es_slug', ''));
        $title   = trim($request->input('title', ''));
        $tmdbId  = trim($request->input('tmdb_id', ''));
        $debug   = $request->boolean('debug');
        abort_unless((bool) ($slug || $esSlug || $title), 400);

        $log = [];

        // Build slug candidates — LATAM Spanish (es-MX) is what piratahub.to uses
        $slugCandidates = array_filter(array_unique([$esSlug, $slug]));
        if ($tmdbId) {
            $latamTitle = $this->tmdbLatamTitle($tmdbId);
            $log['latam_title'] = $latamTitle;
            if ($latamTitle) $slugCandidates[] = \Illuminate\Support\Str::slug($latamTitle);
            $slugCandidates = array_unique($slugCandidates);
        }

        $log['slug_candidates'] = array_values($slugCandidates);

        // 3. Try /pelicula/{slug}/ and /{slug}/ for all candidates
        foreach ($slugCandidates as $s) {
            foreach (["https://piratahub.to/pelicula/{$s}/", "https://piratahub.to/{$s}/"] as $url) {
                try {
                    $response = Http::withHeaders($this->headers + ['Referer' => 'https://piratahub.to/'])->timeout(12)->get($url);
                    $log['tried'][] = $url . ' → ' . $response->status();
                    if (!$response->successful()) continue;
                    $result = $this->extractEmbed($response->body(), 'piratahub.to');
                    if ($result) return response()->json($result + ['matched_url' => $url]);
                } catch (\Throwable $e) {
                    $log['tried'][] = $url . ' → error: ' . $e->getMessage();
                }
            }
        }

        // 4. Piratahub site search — scrape /?s=keywords and follow /pelicula/ links
        if ($title) {
            $keywords = trim(explode(':', $title)[0]); // "Jack Ryan de Tom Clancy"
            $log['site_search_term'] = $keywords;

            // Significant words from the title (>3 chars, ignore common Spanish articles)
            $stopWords  = ['de', 'del', 'la', 'el', 'los', 'las', 'en', 'por', 'con', 'un', 'una', 'the', 'of'];
            $sigWords   = array_filter(
                explode(' ', strtolower($keywords)),
                fn($w) => \strlen($w) > 3 && !\in_array($w, $stopWords)
            );

            try {
                $searchHtml = Http::withHeaders($this->headers + ['Referer' => 'https://piratahub.to/'])
                    ->timeout(12)
                    ->get('https://piratahub.to/', ['s' => $keywords])
                    ->body();

                preg_match_all('#https://piratahub\.to/pelicula/[^"\'<>\s]+/#', $searchHtml, $links);
                $allLinks = array_unique($links[0] ?? []);

                // Keep only links whose slug contains ≥2 significant words from the title
                $peliculaLinks = array_filter($allLinks, function ($link) use ($sigWords) {
                    $slug    = strtolower(basename(rtrim($link, '/')));
                    $matches = 0;
                    foreach ($sigWords as $w) {
                        if (str_contains($slug, $w)) $matches++;
                    }
                    return $matches >= 2;
                });

                $log['site_search_all']      = $allLinks;
                $log['site_search_filtered'] = array_values($peliculaLinks);

                foreach ($peliculaLinks as $link) {
                    try {
                        $response = Http::withHeaders($this->headers + ['Referer' => 'https://piratahub.to/'])->timeout(12)->get($link);
                        if (!$response->successful()) continue;
                        $result = $this->extractEmbed($response->body(), 'piratahub.to');
                        if ($result) return response()->json($result + ['matched_url' => $link]);
                    } catch (\Throwable) {}
                }
            } catch (\Throwable $e) {
                $log['site_search_error'] = $e->getMessage();
            }
        }

        $response = ['error' => 'Filme não encontrado no piratahub.to'];
        if ($debug) $response['debug'] = $log;
        return response()->json($response, 404);
    }

    // piratahub.to uses Latin American Spanish titles (es-MX), not Spain Spanish (es-ES)
    private function tmdbLatamTitle(string $tmdbId, string $type = 'movie'): ?string
    {
        $key = config('services.tmdb.key');
        if (!$key) return null;
        try {
            $endpoint = $type === 'tv' ? "tv/{$tmdbId}" : "movie/{$tmdbId}";
            $data = Http::timeout(8)
                ->get("https://api.themoviedb.org/3/{$endpoint}", ['api_key' => $key, 'language' => 'es-MX'])
                ->json();
            return $data['name'] ?? $data['title'] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    // Try multiple piratahub.to URL patterns and return embed URL from the first that works
    public function find(Request $request)
    {
        $slug    = trim($request->input('slug', ''));
        $tmdbId  = trim($request->input('tmdb_id', ''));
        $season  = (int) $request->input('season', 1);
        $episode = (int) $request->input('episode', 1);

        abort_unless($slug !== '', 400);

        // Build slug candidates — also try es-MX title (what piratahub uses)
        $slugs = [$slug];
        if ($tmdbId) {
            $latamTitle = $this->tmdbLatamTitle($tmdbId, 'tv');
            if ($latamTitle) $slugs[] = \Illuminate\Support\Str::slug($latamTitle);
            $slugs = array_unique($slugs);
        }

        $candidates = [];
        foreach ($slugs as $s) {
            $candidates[] = "https://piratahub.to/{$s}-temporada-{$season}/capitulo-{$episode}/";
            if ($season === 1) {
                $candidates[] = "https://piratahub.to/{$s}/capitulo-{$episode}/";
            }
            $candidates[] = "https://piratahub.to/{$s}/temporada-{$season}-capitulo-{$episode}/";
        }

        foreach (array_unique($candidates) as $url) {
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
