<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Serie;
use App\Services\CinemaCityService;
use Illuminate\Http\JsonResponse;

class CinemaCityController extends Controller
{
    public function __construct(private CinemaCityService $service) {}

    public function episode(Serie $serie, int $season, int $episode): JsonResponse
    {
        if (!$serie->cinemacity_id) {
            return response()->json(['error' => 'not_configured'], 404);
        }

        $url = $this->service->getEpisodeUrl($serie->cinemacity_id, $season, $episode);

        if (!$url) {
            return response()->json(['error' => 'not_found'], 404);
        }

        return response()->json(['url' => $url]);
    }

    public function movie(Movie $movie): JsonResponse
    {
        if (!$movie->cinemacity_id) {
            return response()->json(['error' => 'not_configured'], 404);
        }

        $url = $this->service->getMovieUrl($movie->cinemacity_id);

        if (!$url) {
            return response()->json(['error' => 'not_found'], 404);
        }

        return response()->json(['url' => $url]);
    }
}
