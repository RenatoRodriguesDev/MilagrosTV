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

    // Subdl language codes differ from standard — PT-BR is BR_PT in Subdl
    private function toSubdlLangs(string $langs): string
    {
        $map = ['PT' => 'PT,BR_PT', 'ES' => 'ES', 'EN' => 'EN'];
        return collect(explode(',', $langs))
            ->flatMap(fn($l) => explode(',', $map[trim($l)] ?? $l))
            ->unique()->implode(',');
    }

    public function search(
        string $query,
        string $languages = 'PT,BR_PT,EN',
        ?string $type = null,
        ?int $season = null,
        ?int $episode = null
    ): array {
        $params = [
            'api_key'   => $this->apiKey,
            'film_name' => $query,
            'languages' => $this->toSubdlLangs($languages),
        ];

        if ($type)   $params['type']          = $type;
        if ($season) $params['season_number'] = $season;

        $response = Http::timeout(10)->get("{$this->baseUrl}/subtitles", $params);

        if (!$response->successful()) return [];

        $subs = $response->json()['subtitles'] ?? [];

        return collect($subs)
            ->when($season, fn($col) => $col->filter(
                fn($s) => empty($s['season']) || $s['season'] == $season
            ))
            ->when($episode, fn($col) => $col->filter(function ($s) use ($episode) {
                if (empty($s['season'])) return true;
                $from = $s['episode_from'] ?? $s['episode'] ?? null;
                $end  = $s['episode_end']  ?? $s['episode'] ?? null;
                return !$from || ($episode >= $from && $episode <= ($end ?? $from));
            }))
            ->map(fn($s) => [
                'name'     => $s['release_name'] ?? $s['name'] ?? 'Legenda',
                'lang'     => $s['lang'] ?? $s['language'] ?? '',
                'lang_code'=> strtolower($s['language'] ?? $s['lang_code'] ?? ''),
                'url'      => $s['url'] ?? '',
                'hi'       => $s['hi'] ?? false,
                'season'   => $s['season'] ?? null,
                'episode'  => $s['episode'] ?? null,
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

        // SRT → WebVTT
        return "WEBVTT\n\n" . preg_replace('/(\d{2}:\d{2}:\d{2}),(\d{3})/', '$1.$2', $srtContent);
    }
}
