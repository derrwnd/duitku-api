<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| SCHEDULED COMMANDS
|--------------------------------------------------------------------------
| Jalankan recurring transactions setiap hari jam 00:05
*/

Schedule::command('recurring:process')->dailyAt('00:05');
