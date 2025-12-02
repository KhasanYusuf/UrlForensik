<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Register monitor:sites command to ensure it's available via Artisan routing
Artisan::command('monitor:sites', function () {
    $cmd = app(\App\Console\Commands\MonitorSites::class);
    $cmd->setLaravel(app());
    $cmd->handle();
})->describe('Run defacement integrity checks for all monitored sites');
