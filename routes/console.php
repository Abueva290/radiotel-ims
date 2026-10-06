<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Automatic database backup (spatie/laravel-backup)
|--------------------------------------------------------------------------
| Runs at lunch time, when the office computer is surely turned on.
| 1) backup:clean removes old backups so storage does not fill up
| 2) backup:run --only-db makes a fresh copy of the database
*/
Schedule::command('backup:clean')->dailyAt('12:00');
Schedule::command('backup:run --only-db')->dailyAt('12:05');