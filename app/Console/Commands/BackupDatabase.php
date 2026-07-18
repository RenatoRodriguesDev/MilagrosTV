<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature   = 'db:backup';
    protected $description = 'Backup the SQLite database, keeping the last 7 copies';

    public function handle(): void
    {
        $dbPath = database_path('database.sqlite');

        if (!file_exists($dbPath)) {
            $this->error("Database file not found: {$dbPath}");
            return;
        }

        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename   = 'database_' . now()->format('Y-m-d_His') . '.sqlite';
        $backupPath = $backupDir . '/' . $filename;

        if (!copy($dbPath, $backupPath)) {
            $this->error('Failed to copy database.');
            return;
        }

        $this->info("Backup created: {$filename}");

        $backups = glob($backupDir . '/database_*.sqlite');
        usort($backups, fn ($a, $b) => strcmp($b, $a));

        foreach (array_slice($backups, 7) as $old) {
            unlink($old);
            $this->line('Deleted old backup: ' . basename($old));
        }
    }
}