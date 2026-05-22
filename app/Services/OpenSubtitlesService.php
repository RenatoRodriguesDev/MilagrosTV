<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenSubtitlesService
{
    private string $apiKey;
    private string $username;
    private string $password;
    private string $baseUrl = 'https://api.opensubtitles.com/api/v1';
    private string $userAgent = 'MilagrosTV v1.0';

    public function __construct()
    {
        $this->apiKey   = config('services.opensubtitles.key', '');
        $this->username = config('services.opensubtitles.username', '');
        $this->password = config('services.opensubtitles.password', '');
    }

    private function headers(bool $withAuth = false): array
    {
        $headers = [
            'Api-Key'      => $this->apiKey,
            'User-Agent'   => $this->userAgent,
            'Content-Type' => 'application/json',
        ];

        if ($withAuth && $this->username) {
            $token = $this->login();
            if ($token) $headers['Authorization'] = 'Bearer ' . $token;
        }

        return $headers;
    }

    public function search(
        string $query,
        ?string $type = null,
        ?int $season = null,
        ?int $episode = null
    ): array {
        $params = [
            'query'     => $query,
            'languages' => 'pt,en,es,pt-br',
        ];

        if ($type === 'movie') $params['type'] = 'movie';
        if ($type === 'tv')    $params['type'] = 'episode';
        if ($season)  $params['season_number']  = $season;
        if ($episode) $params['episode_number']  = $episode;

        $response = Http::withHeaders($this->headers())
            ->timeout(10)
            ->get("{$this->baseUrl}/subtitles", $params);

        if (!$response->successful()) return [];

        $data = $response->json()['data'] ?? [];

        return collect($data)
            ->map(function ($r) {
                $attrs   = $r['attributes'] ?? [];
                $file    = $attrs['files'][0] ?? [];
                $details = $attrs['feature_details'] ?? [];
                $lang    = strtolower($attrs['language'] ?? '');

                return [
                    'name'         => $attrs['release'] ?? $file['file_name'] ?? 'Legenda',
                    'lang'         => $attrs['language'] ?? '',
                    'lang_code'    => in_array($lang, ['pt', 'pt-br']) ? 'pt' : $lang,
                    'file_id'      => $file['file_id'] ?? null,
                    'hi'           => $attrs['hearing_impaired'] ?? false,
                    'season'       => $details['season_number'] ?? null,
                    'episode'      => $details['episode_number'] ?? null,
                    'episode_from' => $details['episode_number'] ?? null,
                    'episode_end'  => $details['episode_number'] ?? null,
                    'downloads'    => $attrs['download_count'] ?? 0,
                ];
            })
            ->filter(fn($r) => $r['file_id'])
            ->values()
            ->toArray();
    }

    public function downloadVtt(int $fileId): string
    {
        $token = $this->login();
        $downloadUrl = $this->curlPost("{$this->baseUrl}/download", ['file_id' => $fileId], $token)['link']
            ?? throw new \Exception('OpenSubtitles: sem link de download.');

        $srt = $this->curlGet($downloadUrl);

        // Convert encoding — SRT files from PT/ES sources are often Windows-1252
        if (!mb_check_encoding($srt, 'UTF-8')) {
            $srt = mb_convert_encoding($srt, 'UTF-8', 'Windows-1252');
        }

        return "WEBVTT\n\n" . preg_replace('/(\d{2}:\d{2}:\d{2}),(\d{3})/', '$1.$2', $srt);
    }

    private function login(): ?string
    {
        if (!$this->username || !$this->password) return null;

        $data = $this->curlPost("{$this->baseUrl}/login", [
            'username' => $this->username,
            'password' => $this->password,
        ]);

        return $data['token'] ?? null;
    }

    private function curlPost(string $url, array $body, ?string $token = null): array
    {
        $headers = [
            'Api-Key: ' . $this->apiKey,
            'User-Agent: ' . $this->userAgent,
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        if ($token) $headers[] = 'Authorization: Bearer ' . $token;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($res, true) ?? [];
        if ($code < 200 || $code >= 300) {
            $msg = $json['message'] ?? $code;
            throw new \Exception("OpenSubtitles: {$msg}");
        }
        return $json;
    }

    private function curlGet(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res ?: '';
    }
}
