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

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // Transcode via FFmpeg when browser doesn't support the format
        // Safari/iOS doesn't support MKV or AC3 audio
        if ($this->needsTranscode($ext)) {
            return $this->streamTranscoded($path);
        }

        return $this->streamDirect($path, $ext);
    }

    private function needsTranscode(string $ext): bool
    {
        // MKV and AVI are never natively supported by Safari
        if (in_array($ext, ['mkv', 'avi'])) return true;

        // For MP4, check if User-Agent is Safari/iOS (might have AC3)
        $ua = request()->header('User-Agent', '');
        $isSafari = str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome');
        return $isSafari && $ext === 'mp4';
    }

    private function streamTranscoded(string $path): StreamedResponse
    {
        // Copy video as-is, convert audio to AAC — very fast, no re-encoding
        $cmd = [
            'ffmpeg', '-hide_banner', '-loglevel', 'error',
            '-i', $path,
            '-c:v', 'copy',
            '-c:a', 'aac',
            '-ac', '2',          // stereo (some AC3 5.1 tracks need downmix)
            '-f', 'mp4',
            '-movflags', 'frag_keyframe+empty_moov+faststart',
            'pipe:1',
        ];

        // Handle seek via Range — FFmpeg -ss flag
        $start = 0;
        if (request()->hasHeader('Range')) {
            preg_match('/bytes=(\d+)-/', request()->header('Range'), $m);
            // Convert byte offset to approximate seconds (rough estimate at 1Mbps avg)
            // FFmpeg will seek to the right place
            $start = (int) ($m[1] / 125000); // bytes → seconds at ~1Mbps
            if ($start > 0) {
                array_splice($cmd, 1, 0, ['-ss', (string) $start]);
            }
        }

        return response()->stream(function () use ($cmd) {
            $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            if (!$proc) return;
            fclose($pipes[2]);
            while (!feof($pipes[1])) {
                echo fread($pipes[1], 65536);
                flush();
            }
            fclose($pipes[1]);
            proc_close($proc);
        }, 200, [
            'Content-Type'  => 'video/mp4',
            'Accept-Ranges' => 'none',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function streamDirect(string $path, string $ext): StreamedResponse
    {
        $size   = filesize($path);
        $mime   = $this->mimeType($ext);
        $start  = 0;
        $end    = $size - 1;
        $status = 200;
        $headers = [
            'Content-Type'   => $mime,
            'Accept-Ranges'  => 'bytes',
            'Content-Length' => $size,
            'Cache-Control'  => 'no-cache, no-store',
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
                $chunk      = min(1024 * 256, $remaining);
                $remaining -= $chunk;
                echo fread($fp, $chunk);
                flush();
            }
            fclose($fp);
        }, $status, $headers);
    }

    private function mimeType(string $ext): string
    {
        return match ($ext) {
            'mp4'  => 'video/mp4',
            'mkv'  => 'video/x-matroska',
            'webm' => 'video/webm',
            'avi'  => 'video/x-msvideo',
            'mov'  => 'video/quicktime',
            default => 'application/octet-stream',
        };
    }
}
