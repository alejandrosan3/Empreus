<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Locate new lots once a night with free geocoders (see config/services.php).
Schedule::command('lots:geocode --limit=500')->dailyAt('03:00')->withoutOverlapping();
