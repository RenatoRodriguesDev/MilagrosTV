<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class JackettService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.jackett.url'), '/');
        $this->apiKey  = config('services.jackett.key');
    }

    public function search(string $query, string $type = 'all'): array
    {
        $categories = match ($type) {
            'movie'  => [2000, 2010, 2020, 2030, 2040, 2045, 2050, 2060],
            'series' => [5000, 5010, 5020, 5030, 5040, 5045, 5050, 5060, 5070, 5080],
            default  => [2000, 5000],
        };

        $response = Http::timeout(15)->get("{$this->baseUrl}/api/v2.0/indexers/all/results", [
            'apikey'     => $this->apiKey,
            'Query'      => $query,
            'Category'   => $categories,
        ]);

        if (!$response->successful()) return [];

        $results = $response->json()['Results'] ?? [];

        // Se a query contém código de episódio (S01E02), filtra resultados pelo mesmo código
        $episodeFilter = null;
        if (preg_match('/[Ss](\d{1,2})[Ee](\d{1,3})/', $query, $m)) {
            $episodeFilter = strtolower($m[0]); // ex: s01e02
        }

        return collect($results)
            ->map(fn($r) => [
                'title'      => $r['Title'] ?? '',
                'size'       => $this->formatSize($r['Size'] ?? 0),
                'seeders'    => $r['Seeders'] ?? 0,
                'peers'      => $r['Peers'] ?? 0,
                'indexer'    => $r['Tracker'] ?? '',
                'magnet'     => $r['MagnetUri'] ?? null,
                'link'       => $r['Link'] ?? null,
                'published'  => isset($r['PublishDate']) ? substr($r['PublishDate'], 0, 10) : null,
            ])
            ->filter(fn($r) => $r['magnet'] || $r['link'])
            ->when($episodeFilter, fn($col) => $col->filter(
                fn($r) => str_contains(strtolower($r['title']), $episodeFilter)
            ))
            ->sortByDesc('seeders')
            ->values()
            ->take(30)
            ->toArray();
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes === 0) return '—';
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 1) . ' MB';
        return round($bytes / 1024) . ' KB';
    }
}
