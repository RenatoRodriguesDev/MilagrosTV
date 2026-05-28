<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use Illuminate\Http\Request;

class FileDetectionController extends Controller
{
    public function scan(Request $request)
    {
        $videosPath = storage_path('app/videos');

        if (!is_dir($videosPath)) {
            return response()->json(['error' => 'Pasta de vídeos não encontrada.'], 404);
        }

        $videoExts   = ['mp4', 'mkv', 'avi', 'mov', 'webm'];
        $linkedPaths = Episode::whereNotNull('video_path')->pluck('video_path')->toArray();
        $found       = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($videosPath, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, $videoExts)) continue;

            $relativePath = ltrim(str_replace($videosPath, '', $file->getPathname()), DIRECTORY_SEPARATOR . '/');
            $relativePath = str_replace('\\', '/', $relativePath);

            if (in_array($relativePath, $linkedPaths)) continue;

            // Try to detect season/episode from filename
            $filename = $file->getBasename();
            $season   = null;
            $episode  = null;

            if (preg_match('/[Ss](\d{1,2})[Ee](\d{1,3})/i', $filename, $m)) {
                $season  = (int) $m[1];
                $episode = (int) $m[2];
            } elseif (preg_match('/(\d{1,2})x(\d{1,3})/i', $filename, $m)) {
                $season  = (int) $m[1];
                $episode = (int) $m[2];
            }

            $found[] = [
                'path'     => $relativePath,
                'filename' => $filename,
                'size'     => $this->formatSize($file->getSize()),
                'season'   => $season,
                'episode'  => $episode,
                'folder'   => basename($file->getPath()),
            ];
        }

        usort($found, fn($a, $b) => strcmp($a['path'], $b['path']));

        return response()->json($found);
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 1) . ' MB';
        return round($bytes / 1024, 1) . ' KB';
    }
}
