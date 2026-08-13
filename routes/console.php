<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Process recurring transactions daily
Artisan::command('recurring:process', function () {
    $service = app(\App\Services\RecurringTransactionService::class);
    $count = $service->processDue();
    $this->info("Processed {$count} recurring transaction(s).");
})->purpose('Process due recurring transactions');

Schedule::command('recurring:process')->daily();
Schedule::command('bills:remind')->dailyAt('08:00');
