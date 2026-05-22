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

        if ($this->needsTranscode($ext, $path)) {
            return $this->streamTranscoded($path);
        }

        return $this->streamDirect($path, $ext);
    }

    private function needsTranscode(string $ext, string $path): bool
    {
        // MKV/AVI are never supported by Safari
        if (in_array($ext, ['mkv', 'avi'])) return true;

        // For MP4, only transcode if audio codec is not browser-compatible
        if ($ext === 'mp4') {
            return $this->hasIncompatibleAudio($path);
        }

        return false;
    }

    private function hasIncompatibleAudio(string $path): bool
    {
        $codec = trim((string) shell_exec(
            'ffprobe -v quiet -select_streams a:0 -show_entries stream=codec_name -of csv=p=0 '
            . escapeshellarg($path) . ' 2>/dev/null'
        ));

        // AC3, EAC3, DTS, TrueHD are not supported by Safari/iOS
        return in_array(strtolower($codec), ['ac3', 'eac3', 'dts', 'truehd', 'mlp']);
    }

    private function streamTranscoded(string $path): StreamedResponse
    {
        $cmd = 'ffmpeg -hide_banner -loglevel error'
            . ' -i ' . escapeshellarg($path)
            . ' -c:v copy'
            . ' -c:a aac -ac 2 -b:a 192k'
            . ' -f mp4 -movflags frag_keyframe+empty_moov'
            . ' pipe:1 2>/dev/null';

        return response()->stream(function () use ($cmd) {
            $handle = popen($cmd, 'r');
            if (!$handle) return;
            while (!feof($handle)) {
                echo fread($handle, 65536);
                flush();
            }
            pclose($handle);
        }, 200, [
            'Content-Type'      => 'video/mp4',
            'Accept-Ranges'     => 'none',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function streamDirect(string $path, string $ext): StreamedResponse
    {
        $size    = filesize($path);
        $mime    = $this->mimeType($ext);
        $start   = 0;
        $end     = $size - 1;
        $status  = 200;
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
            'mp4'   => 'video/mp4',
            'mkv'   => 'video/x-matroska',
            'webm'  => 'video/webm',
            'avi'   => 'video/x-msvideo',
            'mov'   => 'video/quicktime',
            default => 'application/octet-stream',
        };
    }
}
