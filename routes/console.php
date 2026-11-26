<?php

use App\Mail\GatewayAlertMail;
use App\Models\WebhookLog;
use App\Services\MaintenanceBillingService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
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

Artisan::command('q4:remind-maintenance {--force : Send even if already reminded this month}', function (MaintenanceBillingService $billing) {
    $count = $billing->sendUpcomingReminders((bool) $this->option('force'));
    $this->info("Maintenance notice emails dispatched: {$count}");
})->purpose('Send 20th-of-month maintenance reminder emails to active clients');

Artisan::command('q4:health-check {--threshold=5 : Minimum failed webhooks in 60 minutes to alert}', function () {
    $threshold = max(1, (int) $this->option('threshold'));
    $windowMinutes = 60;
    $failures = WebhookLog::query()
        ->whereNotIn('outcome', ['ok', 'Success', 'charge.success', 'reconciled'])
        ->where('created_at', '>=', now()->subMinutes($windowMinutes))
        ->orderByDesc('id')
        ->get();

    $count = $failures->count();
    $adminEmail = (string) config('services.alerts.admin_email');
    if ($count >= $threshold && $adminEmail !== '') {
        Mail::to($adminEmail)->send(new GatewayAlertMail($count, $windowMinutes, $failures->first()?->outcome));
        $this->warn("Gateway anomaly alert sent to {$adminEmail} ({$count} errors).");

        return;
    }

    $this->info("Gateway healthy ({$count} webhook errors in last {$windowMinutes}m, threshold {$threshold}).");
})->purpose('Monitor USSD/billing webhooks for anomaly spikes');

Schedule::command('q4:bill-maintenance --suspend')->monthlyOn(1, '00:05');
Schedule::command('q4:remind-maintenance')->monthlyOn(20, '08:00');
Schedule::command('q4:health-check')->hourly();
