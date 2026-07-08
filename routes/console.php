<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// =====================================================
// CHECK MAINTENANCE ALERTS EVERY HOUR
// =====================================================

Schedule::command('maintenance:check-alerts')

    ->hourly()

    ->withoutOverlapping();
