<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class MonitorController extends Controller
{
    public function index()
    {
        return view('admin.monitor');
    }

    public function stats()
    {
        // CPU load
        $load = sys_getloadavg();

        // Memory
        $memFree = $memTotal = 0;
        if (PHP_OS_FAMILY === 'Linux' && file_exists('/proc/meminfo')) {
            foreach (file('/proc/meminfo') as $line) {
                if (str_starts_with($line, 'MemTotal:'))     $memTotal = (int) preg_replace('/\D/', '', $line);
                if (str_starts_with($line, 'MemAvailable:')) $memFree  = (int) preg_replace('/\D/', '', $line);
            }
        }
        $memUsed = $memTotal - $memFree;

        // Disk (videos folder)
        $videosPath = storage_path('app/videos');
        $diskTotal  = disk_total_space($videosPath) ?: disk_total_space('/');
        $diskFree   = disk_free_space($videosPath) ?: disk_free_space('/');
        $diskUsed   = $diskTotal - $diskFree;

        // PHP memory
        $phpMem = memory_get_usage(true);

        return response()->json([
            'cpu'  => [
                'load1'  => round($load[0], 2),
                'load5'  => round($load[1], 2),
                'load15' => round($load[2], 2),
            ],
            'memory' => [
                'total'   => $memTotal * 1024,
                'used'    => $memUsed * 1024,
                'free'    => $memFree * 1024,
                'percent' => $memTotal > 0 ? round($memUsed / $memTotal * 100) : 0,
            ],
            'disk' => [
                'total'   => $diskTotal,
                'used'    => $diskUsed,
                'free'    => $diskFree,
                'percent' => $diskTotal > 0 ? round($diskUsed / $diskTotal * 100) : 0,
            ],
            'php_memory' => $phpMem,
        ]);
    }
}
