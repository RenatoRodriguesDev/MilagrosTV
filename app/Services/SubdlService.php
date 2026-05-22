<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use ZipArchive;

class SubdlService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.subdl.com/api/v1';

    public function __construct()
    {
        $this->apiKey = config('services.subdl.key', '');
    }

    public function search(
        string $query,
        ?string $type = null,
        ?int $season = null,
        ?int $episode = null
    ): array {
        // Don't filter by language in API — Subdl codes are inconsistent.
        // Language filtering is done client-side via the lang buttons.
        $params = [
            'api_key'   => $this->apiKey,
            'film_name' => $query,
        ];

        if ($type)    $params['type']           = $type;
        if ($season)  $params['season_number']  = $season;
        if ($episode) $params['episode_number'] = $episode;

        $response = Http::timeout(10)->get("{$this->baseUrl}/subtitles", $params);

        if (!$response->successful()) return [];

        $subs = $response->json()['subtitles'] ?? [];

        return collect($subs)
            ->when($season, function ($col) use ($season) {
                return $col->filter(function ($s) use ($season) {
                    // Check season field if present
                    if (!empty($s['season']) && (int)$s['season'] !== $season) return false;
                    // Also verify via release name (Subdl API sometimes returns wrong season)
                    $name = $s['release_name'] ?? $s['name'] ?? '';
                    if (preg_match('/\bS(\d{1,2})E\d/i', $name, $m)) {
                        if ((int)$m[1] !== $season) return false;
                    }
                    return true;
                });
            })
            ->when($episode, fn($col) => $col->filter(function ($s) use ($episode) {
                $from = $s['episode_from'] ?? $s['episode'] ?? null;
                $end  = $s['episode_end']  ?? null;
                if (!$from) return false; // sem episódio definido → exclui
                $from = (int) $from;
                $end  = $end !== null ? (int) $end : $from;
                // Só aceita se for exatamente este episódio (não packs multi-episódio)
                return $from === $episode && $end === $episode;
            }))
            ->map(fn($s) => [
                'name'         => $s['release_name'] ?? $s['name'] ?? 'Legenda',
                'lang'         => $s['lang'] ?? $s['language'] ?? '',
                'lang_code'    => strtolower($s['language'] ?? $s['lang_code'] ?? ''),
                'url'          => $s['url'] ?? '',
                'hi'           => $s['hi'] ?? false,
                'season'       => $s['season'] ?? null,
                'episode'      => $s['episode'] ?? null,
                'episode_from' => $s['episode_from'] ?? null,
                'episode_end'  => $s['episode_end'] ?? null,
            ])->values()->toArray();
    }

    public function downloadVtt(string $subUrl): string
    {
        $zipContent = Http::timeout(30)->get("https://dl.subdl.com{$subUrl}")->body();

        $tmpZip = tempnam(sys_get_temp_dir(), 'subdl_') . '.zip';
        file_put_contents($tmpZip, $zipContent);

        $zip = new ZipArchive();
        if ($zip->open($tmpZip) !== true) {
            unlink($tmpZip);
            throw new \Exception('Falha ao abrir o zip de legendas.');
        }

        $srtContent = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_ends_with(strtolower($name), '.srt')) {
                $srtContent = $zip->getFromIndex($i);
                break;
            }
        }
        $zip->close();
        unlink($tmpZip);

        if (!$srtContent) {
            throw new \Exception('Nenhum ficheiro .srt encontrado na legenda.');
        }

        // Detect and convert encoding to UTF-8 (SRT files are often ISO-8859-1/Windows-1252)
        $encoding = mb_detect_encoding($srtContent, ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'ISO-8859-15'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $srtContent = mb_convert_encoding($srtContent, 'UTF-8', $encoding);
        }

        // SRT → WebVTT
        return "WEBVTT\n\n" . preg_replace('/(\d{2}:\d{2}:\d{2}),(\d{3})/', '$1.$2', $srtContent);
    }
}
