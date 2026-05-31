<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TmdbService
{
    private string $baseUrl = 'https://api.themoviedb.org/3';
    private string $imageBase = 'https://image.tmdb.org/t/p/w500';

    private function get(string $path, array $params = []): array
    {
        $response = Http::get("{$this->baseUrl}{$path}", array_merge([
            'api_key'  => config('services.tmdb.key'),
            'language' => config('services.tmdb.language', 'pt-BR'),
        ], $params));

        return $response->successful() ? $response->json() : [];
    }

    public function searchMovie(string $query): array
    {
        $data = $this->get('/search/movie', ['query' => $query]);
        return array_slice($data['results'] ?? [], 0, 5);
    }

    public function searchSeries(string $query): array
    {
        $data = $this->get('/search/tv', ['query' => $query]);
        return array_slice($data['results'] ?? [], 0, 5);
    }

    public function getMovieDetails(int $tmdbId): array
    {
        return $this->get("/movie/{$tmdbId}");
    }

    public function getSeriesDetails(int $tmdbId): array
    {
        return $this->get("/tv/{$tmdbId}");
    }

    public function formatMovieData(array $tmdb): array
    {
        $genres = array_map(fn($g) => $g['name'], $tmdb['genres'] ?? []);
        if (empty($genres) && isset($tmdb['genre_ids'])) {
            $genres = $this->resolveGenreNames($tmdb['genre_ids'], 'movie');
        }

        return [
            'title'          => $tmdb['title'] ?? $tmdb['original_title'] ?? '',
            'original_title' => $tmdb['original_title'] ?? null,
            'year'           => isset($tmdb['release_date']) ? (int) substr($tmdb['release_date'], 0, 4) : null,
            'genres'         => $genres,
            'synopsis'       => $tmdb['overview'] ?? null,
            'poster_url'     => isset($tmdb['poster_path']) ? $this->imageBase . $tmdb['poster_path'] : null,
            'tmdb_id'        => (string) ($tmdb['id'] ?? ''),
            'rating'         => isset($tmdb['vote_average']) ? round($tmdb['vote_average'], 1) : null,
            'duration'       => $tmdb['runtime'] ?? null,
        ];
    }

    public function formatSeriesData(array $tmdb): array
    {
        $genres = array_map(fn($g) => $g['name'], $tmdb['genres'] ?? []);
        if (empty($genres) && isset($tmdb['genre_ids'])) {
            $genres = $this->resolveGenreNames($tmdb['genre_ids'], 'tv');
        }

        return [
            'title'          => $tmdb['name'] ?? $tmdb['original_name'] ?? '',
            'original_title' => $tmdb['original_name'] ?? null,
            'year'           => isset($tmdb['first_air_date']) ? (int) substr($tmdb['first_air_date'], 0, 4) : null,
            'genres'         => $genres,
            'synopsis'       => $tmdb['overview'] ?? null,
            'poster_url'     => isset($tmdb['poster_path']) ? $this->imageBase . $tmdb['poster_path'] : null,
            'tmdb_id'        => (string) ($tmdb['id'] ?? ''),
            'rating'         => isset($tmdb['vote_average']) ? round($tmdb['vote_average'], 1) : null,
            'seasons'        => $tmdb['number_of_seasons'] ?? null,
        ];
    }

    private function resolveGenreNames(array $ids, string $type): array
    {
        $data = $this->get("/genre/{$type}/list");
        $map = collect($data['genres'] ?? [])->keyBy('id');
        return collect($ids)->map(fn($id) => $map[$id]['name'] ?? null)->filter()->values()->toArray();
    }

    private function resolveGenreNamesInLanguage(array $ids, string $type, string $language): array
    {
        $response = Http::get("{$this->baseUrl}/genre/{$type}/list", [
            'api_key'  => config('services.tmdb.key'),
            'language' => $language,
        ]);
        $map = collect($response->json()['genres'] ?? [])->keyBy('id');
        return collect($ids)->map(fn($id) => $map[$id]['name'] ?? null)->filter()->values()->toArray();
    }

    public function fetchTranslations(int $tmdbId, string $type): array
    {
        $langMap  = ['pt' => 'pt-BR', 'en' => 'en-US', 'es' => 'es-ES'];
        $titleKey = $type === 'tv' ? 'name' : 'title';
        $translations = [];

        foreach ($langMap as $appLang => $tmdbLang) {
            $data = $this->getWithLanguage("/{$type}/{$tmdbId}", $tmdbLang);
            if (empty($data)) continue;

            $genres = array_map(fn($g) => $g['name'], $data['genres'] ?? []);
            if (empty($genres) && isset($data['genre_ids'])) {
                $genres = $this->resolveGenreNamesInLanguage($data['genre_ids'], $type, $tmdbLang);
            }

            $translations[$appLang] = [
                'title'    => $data[$titleKey] ?? $data['original_' . $titleKey] ?? null,
                'synopsis' => $data['overview'] ?? null,
                'genres'   => $genres,
            ];
        }

        return $translations;
    }

    private function getWithLanguage(string $path, string $language): array
    {
        $response = Http::get("{$this->baseUrl}{$path}", [
            'api_key'  => config('services.tmdb.key'),
            'language' => $language,
        ]);
        return $response->successful() ? $response->json() : [];
    }

    public function getSeasonEpisodes(int $tmdbId, int $season): array
    {
        $data = $this->get("/tv/{$tmdbId}/season/{$season}");
        return $data['episodes'] ?? [];
    }

    public function posterUrl(?string $path): ?string
    {
        return $path ? $this->imageBase . $path : null;
    }

    public function getTrailerUrl(int $tmdbId, string $type = 'movie'): ?string
    {
        $endpoint = $type === 'movie' ? "/movie/{$tmdbId}/videos" : "/tv/{$tmdbId}/videos";
        $data = $this->get($endpoint);
        $videos = $data['results'] ?? [];

        $trailer = collect($videos)
            ->where('site', 'YouTube')
            ->whereIn('type', ['Trailer', 'Teaser'])
            ->sortByDesc(fn($v) => $v['type'] === 'Trailer' ? 1 : 0)
            ->first();

        return $trailer ? 'https://www.youtube.com/embed/' . $trailer['key'] . '?autoplay=1' : null;
    }

    public function discover(string $type, string $category = 'popular', int $page = 1): array
    {
        $endpoint = $type === 'movie' ? "/movie/{$category}" : "/tv/{$category}";
        $data = $this->get($endpoint, ['page' => $page]);
        return [
            'results'       => $data['results'] ?? [],
            'total_pages'   => $data['total_pages'] ?? 1,
            'total_results' => $data['total_results'] ?? 0,
            'page'          => $data['page'] ?? 1,
        ];
    }

    public function trending(string $type = 'all', string $window = 'week'): array
    {
        $data = $this->get("/trending/{$type}/{$window}");
        return $data['results'] ?? [];
    }
}
