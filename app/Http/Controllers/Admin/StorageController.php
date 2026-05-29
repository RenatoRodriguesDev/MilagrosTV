<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Episode;

class StorageController extends Controller
{
    private string $videosPath;

    public function __construct()
    {
        $this->videosPath = storage_path('app/videos');
    }

    public function index()
    {
        if (!is_dir($this->videosPath)) {
            return view('admin.storage.index', ['files' => collect(), 'totalSize' => 0, 'linkedPaths' => []]);
        }

        $linkedPaths = Episode::whereNotNull('video_path')
            ->where('video_path', 'not like', 'http%')
            ->pluck('video_path', 'video_path')
            ->toArray();

        $videoExts = ['mp4', 'mkv', 'avi', 'mov', 'webm'];
        $files = collect();

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->videosPath, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            if (!in_array(strtolower($file->getExtension()), $videoExts)) continue;

            $relative = ltrim(str_replace($this->videosPath, '', $file->getPathname()), DIRECTORY_SEPARATOR . '/');
            $relative = str_replace('\\', '/', $relative);

            $files->push([
                'path'    => $relative,
                'name'    => $file->getFilename(),
                'size'    => $file->getSize(),
                'linked'  => isset($linkedPaths[$relative]),
                'folder'  => basename($file->getPath()),
            ]);
        }

        $files = $files->sortByDesc('size')->values();
        $totalSize = $files->sum('size');

        return view('admin.storage.index', compact('files', 'totalSize', 'linkedPaths'));
    }
}
