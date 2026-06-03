<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Task 3.1.2: Scheduler harian untuk evaluasi alumni
Schedule::command('anak-asuh:update-alumni')->dailyAt('00:00');
