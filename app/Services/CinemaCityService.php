<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CinemaCityService
{
    private const BASE      = 'https://cinemacity.cc';
    private const CACHE_KEY = 'cinemacity:cookies';
    private const UA        = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36';

    // ── Auth ──────────────────────────────────────────────────────────────────

    private function cookies(): array
    {
        // Prefer manually configured cookies from .env (avoids Cloudflare server-side block)
        $userId   = config('services.cinemacity.cookie_user_id');
        $password = config('services.cinemacity.cookie_password');

        if ($userId && $password) {
            return ['dle_user_id' => $userId, 'dle_password' => $password];
        }

        return Cache::get(self::CACHE_KEY, []);
    }

    // ── Fetch via FlareSolverr (bypasses Cloudflare) ─────────────────────────

    private function getCfClearance(): ?array
    {
        $cacheKey = 'cinemacity:cf_clearance';
        $cached   = Cache::get($cacheKey);
        if ($cached) return $cached;

        $flareSolverrUrl = config('services.cinemacity.flaresolverr', 'http://flaresolverr:8191');

        try {
            $client = new Client();
            $res    = $client->post("{$flareSolverrUrl}/v1", [
                'json'    => ['cmd' => 'request.get', 'url' => self::BASE . '/', 'maxTimeout' => 30000],
                'timeout' => 40,
            ]);

            $data = json_decode((string) $res->getBody(), true);

            if (($data['status'] ?? '') !== 'ok') {
                return null;
            }

            $fsCookies = $data['solution']['cookies'] ?? [];
            $ua        = $data['solution']['userAgent'] ?? self::UA;

            $result = ['cookies' => $fsCookies, 'ua' => $ua];
            // Cache for 20 minutes (cf_clearance is valid for much longer but we refresh proactively)
            Cache::put($cacheKey, $result, now()->addMinutes(20));

            return $result;

        } catch (\Throwable $e) {
            Log::warning("CinemaCity CF clearance failed: " . $e->getMessage());
            return null;
        }
    }

    private function fetchPage(string $url): ?string
    {
        $cfData = $this->getCfClearance();
        if (!$cfData) {
            Log::warning("CinemaCity: could not obtain cf_clearance");
            return null;
        }

        $dleCookies = $this->cookies();
        $ua         = $cfData['ua'];

        // Build cookie header: cf cookies + DLE auth cookies
        $parts = array_map(fn($c) => $c['name'] . '=' . $c['value'], $cfData['cookies']);
        foreach ($dleCookies as $name => $value) {
            $parts[] = $name . '=' . $value;
        }
        $cookieHeader = implode('; ', $parts);

        try {
            $client = new Client(['verify' => false]);
            $res    = $client->get($url, [
                'headers' => [
                    'User-Agent'      => $ua,
                    'Cookie'          => $cookieHeader,
                    'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                    'Referer'         => self::BASE . '/',
                ],
                'allow_redirects' => true,
                'timeout'         => 20,
            ]);

            $html = (string) $res->getBody();

            if (empty($html) || str_contains($html, 'name="login_name"')) {
                Log::warning("CinemaCity: not authenticated (DLE cookies may be expired)");
                return null;
            }

            // Invalidate cf_clearance cache if Cloudflare blocked us
            if (str_contains($html, 'Just a moment') || $res->getStatusCode() === 403) {
                Cache::forget('cinemacity:cf_clearance');
                Log::warning("CinemaCity: Cloudflare blocked, invalidating cf_clearance cache");
                return null;
            }

            return $html;

        } catch (\Throwable $e) {
            Log::warning("CinemaCity fetch failed [{$url}]: " . $e->getMessage());
            return null;
        }
    }

    // ── Parse ─────────────────────────────────────────────────────────────────

    private function extractPlaylist(string $html): ?array
    {
        preg_match_all('/eval\(atob\("([A-Za-z0-9+\/=]+)"\)\)/', $html, $matches);

        foreach ($matches[1] as $b64) {
            $decoded = base64_decode($b64);
            if (!str_contains($decoded, "file:'[")) continue;

            $playlist = $this->parsePlaylistFromJs($decoded);
            if ($playlist) return $playlist;
        }

        return null;
    }

    private function parsePlaylistFromJs(string $js): ?array
    {
        $start = strpos($js, "file:'[");
        if ($start === false) return null;
        $start += 6; // skip "file:'"

        $depth    = 0;
        $inString = false;
        $escape   = false;
        $end      = $start;

        for ($i = $start, $len = \strlen($js); $i < $len; $i++) {
            $c = $js[$i];
            if ($escape)          { $escape = false; continue; }
            if ($c === '\\')      { $escape = true;  continue; }
            if ($c === '"')       { $inString = !$inString; continue; }
            if ($inString)        continue;
            if ($c === '[' || $c === '{') $depth++;
            elseif ($c === ']' || $c === '}') {
                if (--$depth === 0) { $end = $i; break; }
            }
        }

        $json = substr($js, $start, $end - $start + 1);
        return json_decode($json, true) ?: null;
    }

    // ── Public API ────────────────────────────────────────────────────────────

    public function getEpisodeUrl(string $cinemacityId, int $season, int $episode): ?string
    {
        $cacheKey = "cinemacity:ep:{$cinemacityId}:s{$season}e{$episode}";
        $cached   = Cache::get($cacheKey);
        if ($cached) return $cached;

        $html = $this->fetchPage(self::BASE . "/tv-series/{$cinemacityId}.html");
        if (!$html) return null;

        $playlist = $this->extractPlaylist($html);
        if (!$playlist) return null;

        // Find season
        $seasonData = null;
        foreach ($playlist as $s) {
            $title = $s['title'] ?? '';
            if (preg_match('/\b' . $season . '\b/', $title)) {
                $seasonData = $s;
                break;
            }
        }
        if (!$seasonData || empty($seasonData['folder'])) return null;

        // Find episode
        foreach ($seasonData['folder'] as $ep) {
            $title = $ep['title'] ?? '';
            if (preg_match('/\b' . $episode . '\b/', $title)) {
                $url = $ep['file'] ?? null;
                if ($url) {
                    Cache::put($cacheKey, $url, now()->addHours(20));
                    return $url;
                }
            }
        }

        return null;
    }

    public function getMovieUrl(string $cinemacityId): ?string
    {
        $cacheKey = "cinemacity:movie:{$cinemacityId}";
        $cached   = Cache::get($cacheKey);
        if ($cached) return $cached;

        $html = $this->fetchPage(self::BASE . "/movies/{$cinemacityId}.html");
        if (!$html) return null;

        $playlist = $this->extractPlaylist($html);
        if (!$playlist) return null;

        // Movies: first item has the file directly
        $url = $playlist[0]['file'] ?? null;

        if ($url) {
            Cache::put($cacheKey, $url, now()->addHours(20));
        }

        return $url;
    }
}
