<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoController extends Controller
{
    public function stream(Episode $episode): StreamedResponse
    {
        $path = storage_path('app/videos/' . $episode->video_path);

        abort_unless($episode->video_path && file_exists($path), 404);

        $size     = filesize($path);
        $mimeType = $this->mimeType($path);
        $start    = 0;
        $end      = $size - 1;
        $status   = 200;
        $headers  = [
            'Content-Type'              => $mimeType,
            'Accept-Ranges'             => 'bytes',
            'Content-Length'            => $size,
            'Cache-Control'             => 'no-cache, no-store',
        ];

        if (request()->hasHeader('Range')) {
            preg_match('/bytes=(\d+)-(\d*)/', request()->header('Range'), $m);
            $start  = (int) $m[1];
            $end    = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : $size - 1;
            $end    = min($end, $size - 1);
            $length = $end - $start + 1;
            $status = 206;
            $headers['Content-Range']  = "bytes {$start}-{$end}/{$size}";
            $headers['Content-Length'] = $length;
        }

        return response()->stream(function () use ($path, $start, $end) {
            $fp = fopen($path, 'rb');
            fseek($fp, $start);
            $remaining = $end - $start + 1;
            while (!feof($fp) && $remaining > 0) {
                $chunk     = min(1024 * 256, $remaining);
                $remaining -= $chunk;
                echo fread($fp, $chunk);
                flush();
            }
            fclose($fp);
        }, $status, $headers);
    }

    private function mimeType(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'mp4'       => 'video/mp4',
            'mkv'       => 'video/x-matroska',
            'webm'      => 'video/webm',
            'avi'       => 'video/x-msvideo',
            'mov'       => 'video/quicktime',
            default     => 'application/octet-stream',
        };
    }
}
