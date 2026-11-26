<?php

use App\Services\MaintenanceBillingService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('q4:bill-maintenance {--force : Re-bill even if billed this month} {--suspend : Suspend active accounts with insufficient balance}', function (MaintenanceBillingService $billing) {
    $summary = $billing->billDueClients(
        (bool) $this->option('force'),
        (bool) $this->option('suspend')
    );

    $this->info("Maintenance billing finished — Billed: {$summary['billed']}, Insufficient: {$summary['insufficient']}, Skipped: {$summary['skipped']}, Collected: ₦".number_format($summary['total_collected'], 2));
})->purpose('Collect monthly Q4 USSD channel maintenance fees');

Schedule::command('q4:bill-maintenance --suspend')->monthlyOn(1, '00:05');
