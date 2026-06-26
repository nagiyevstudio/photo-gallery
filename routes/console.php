<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

// Every hour: archive expired projects
Schedule::command('projects:expire')->hourly();

// Every 6 hours: delete ZIP files older than 24h
Schedule::command('zips:cleanup')->everySixHours();

// Every minute: process queued jobs
Schedule::command('queue:work --stop-when-empty --max-time=55')->everyMinute()
    ->withoutOverlapping();

