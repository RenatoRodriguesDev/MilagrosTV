<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sync new episodes from TMDB every day at 04:00
Schedule::command('episodes:sync-all')->dailyAt('04:00');

// Auto-match cinemacity IDs every day at 03:30
Schedule::command('cinemacity:match')->dailyAt('03:30')->withoutOverlapping();

// Backup SQLite database every day at 02:00
Schedule::command('db:backup')->dailyAt('02:00');
