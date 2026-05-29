<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LogController extends Controller
{
    private string $logPath;

    public function __construct()
    {
        $this->logPath = storage_path('logs/laravel.log');
    }

    public function index(Request $request)
    {
        $level  = $request->input('level', 'all');
        $search = $request->input('search', '');
        $lines  = $this->parseLog($level, $search);
        $stats  = $this->logStats();

        return view('admin.logs.index', compact('lines', 'level', 'search', 'stats'));
    }

    public function clear()
    {
        if (file_exists($this->logPath)) {
            file_put_contents($this->logPath, '');
        }
        return back()->with('success', 'Log limpo com sucesso.');
    }

    private function parseLog(string $level, string $search): array
    {
        if (!file_exists($this->logPath)) return [];

        $content = file_get_contents($this->logPath);
        $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(\w+): (.+?)(?=\[\d{4}|\z)/s';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        $entries = [];
        foreach (array_reverse($matches) as $m) {
            $entryLevel = strtolower($m[2]);
            if ($level !== 'all' && $entryLevel !== $level) continue;

            $message = trim($m[3]);
            if ($search && !str_contains(strtolower($message), strtolower($search))) continue;

            $entries[] = [
                'date'    => $m[1],
                'level'   => $entryLevel,
                'message' => mb_substr($message, 0, 500),
                'full'    => $message,
            ];

            if (count($entries) >= 200) break;
        }

        return $entries;
    }

    private function logStats(): array
    {
        if (!file_exists($this->logPath)) return [];

        $content = file_get_contents($this->logPath);
        $stats   = ['error' => 0, 'warning' => 0, 'info' => 0, 'debug' => 0, 'size' => filesize($this->logPath)];

        preg_match_all('/\] \w+\.(\w+):/i', $content, $m);
        foreach ($m[1] as $lvl) {
            $k = strtolower($lvl);
            if (isset($stats[$k])) $stats[$k]++;
        }

        return $stats;
    }
}
