<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\Serie;
use App\Services\TmdbService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class EpisodeController extends Controller
{
    public function store(Request $request, Serie $serie)
    {
        $data = $request->validate([
            'season'     => 'required|integer|min:1',
            'episode'    => 'required|integer|min:1',
            'title'      => 'nullable|string|max:255',
            'video_path' => 'nullable|string|max:500',
        ]);

        Episode::updateOrCreate(
            ['serie_id' => $serie->id, 'season' => $data['season'], 'episode' => $data['episode']],
            ['title' => $data['title'] ?? null, 'video_path' => $data['video_path'] ?? null]
        );

        return back()->with('success', 'Episódio guardado.');
    }

    public function importBatch(Request $request, Serie $serie)
    {
        $request->validate(['episodes' => 'required|array']);

        $count = 0;
        foreach ($request->input('episodes') as $ep) {
            if (empty($ep['video_path']) || empty($ep['season']) || empty($ep['episode'])) {
                continue;
            }
            Episode::updateOrCreate(
                ['serie_id' => $serie->id, 'season' => (int) $ep['season'], 'episode' => (int) $ep['episode']],
                ['title' => $ep['title'] ?? null, 'video_path' => $ep['video_path']]
            );
            $count++;
        }

        return response()->json(['imported' => $count]);
    }

    public function scan(Request $request)
    {
        $request->validate(['folder' => 'required|string']);
        $folder   = ltrim(str_replace(['..', '\\'], ['', '/'], $request->input('folder')), '/');
        $basePath = storage_path('app/videos/' . $folder);

        if (!is_dir($basePath)) {
            return response()->json(['error' => 'Pasta não encontrada: ' . $folder], 404);
        }

        $files    = File::allFiles($basePath);
        $detected = [];
        $video    = ['mp4', 'mkv', 'avi', 'webm', 'mov', 'ts', 'm4v'];

        foreach ($files as $file) {
            if (!in_array(strtolower($file->getExtension()), $video)) {
                continue;
            }

            $name = $file->getFilenameWithoutExtension();
            // Detecta padrões: S01E08, s01e08, 1x08
            if (preg_match('/[Ss](\d{1,2})[Ee](\d{1,3})/', $name, $m)
                || preg_match('/(\d{1,2})[xX](\d{1,3})/', $name, $m)) {
                $detected[] = [
                    'season'     => (int) $m[1],
                    'episode'    => (int) $m[2],
                    'title'      => null,
                    'video_path' => $folder . '/' . $file->getRelativePathname(),
                    'filename'   => $file->getFilename(),
                ];
            }
        }

        usort($detected, fn($a, $b) => [$a['season'], $a['episode']] <=> [$b['season'], $b['episode']]);

        return response()->json($detected);
    }

    public function importFromTmdb(Request $request, Serie $serie)
    {
        $request->validate(['season' => 'required|integer|min:1']);

        if (!$serie->tmdb_id) {
            return response()->json(['error' => 'A série não tem TMDB ID configurado.'], 422);
        }

        $tmdb     = app(TmdbService::class);
        $episodes = $tmdb->getSeasonEpisodes((int) $serie->tmdb_id, $request->input('season'));

        if (empty($episodes)) {
            return response()->json(['error' => 'Nenhum episódio encontrado no TMDB para esta temporada.'], 404);
        }

        $season = (int) $request->input('season');
        foreach ($episodes as $ep) {
            Episode::updateOrCreate(
                ['serie_id' => $serie->id, 'season' => $season, 'episode' => $ep['episode_number']],
                ['title' => $ep['name'] ?? null]
            );
        }

        return response()->json(['imported' => count($episodes), 'episodes' => $episodes]);
    }

    public function destroy(Episode $episode)
    {
        $episode->delete();
        return back()->with('success', 'Episódio removido.');
    }
}
