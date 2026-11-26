<?php

namespace App\Services;

use App\Mail\MaintenanceReminderMail;
use App\Models\Client;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class MaintenanceBillingService
{
    public function __construct(
        protected LowBalanceNotifier $notifier,
        protected ?PerfexCrmService $perfex = null
    ) {}

    /**
     * Deduct monthly maintenance fees (default ₦159,500) for active non-admin clients due this month.
     *
     * @return array{billed: int, insufficient: int, skipped: int, total_collected: float}
     */
    public function billDueClients(bool $forceCurrentMonth = false, bool $suspendOnShortfall = false): array
    {
        $monthStart = Carbon::now()->startOfMonth();
        $clients = Client::query()
            ->where(fn ($q) => $q->where('is_admin', false)->orWhereNull('is_admin'))
            ->where('status', 'active')
            ->where('maintenance_enabled', true)
            ->orderBy('id')
            ->get();

        $summary = [
            'billed' => 0,
            'insufficient' => 0,
            'skipped' => 0,
            'total_collected' => 0.0,
        ];

        $perfex = $this->perfex ?? app(PerfexCrmService::class);
        $syncPerfex = (bool) config('services.perfex.auto_sync_invoices', false) && $perfex->isConfigured();

        foreach ($clients as $client) {
            $fee = round((float) ($client->monthly_maintenance_fee ?? 159500.00), 2);
            if ($fee <= 0) {
                $summary['skipped']++;

                continue;
            }

            if (! $forceCurrentMonth && $client->last_maintenance_billed_at && $client->last_maintenance_billed_at->gte($monthStart)) {
                $summary['skipped']++;

                continue;
            }

            $result = DB::transaction(function () use ($client, $fee, $suspendOnShortfall): array {
                $locked = Client::query()->whereKey($client->id)->lockForUpdate()->first();
                if (! $locked || $locked->status !== 'active') {
                    return ['status' => 'skipped', 'transaction' => null];
                }

                if ((float) $locked->token_balance < $fee) {
                    if ($suspendOnShortfall) {
                        $locked->update(['status' => 'suspended']);
                    }

                    return ['status' => 'insufficient', 'transaction' => null];
                }

                $locked->decrement('token_balance', $fee);
                $locked->update(['last_maintenance_billed_at' => now()]);

                $tx = Transaction::create([
                    'client_id' => $locked->id,
                    'type' => 'debit',
                    'reference' => 'MAINT-'.now()->format('Ym').'-'.$locked->id.'-'.random_int(100, 999),
                    'amount' => $fee,
                    'tokens_purchased' => 0,
                    'status' => 'success',
                    'gateway' => 'maintenance_fee',
                    'notes' => 'Monthly USSD Channel Maintenance Fee ('.now()->format('M Y').')',
                    'created_at' => now(),
                ]);

                return ['status' => 'billed', 'transaction' => $tx];
            });

            if ($result['status'] === 'billed') {
                $summary['billed']++;
                $summary['total_collected'] += $fee;
                $client->refresh();
                $this->notifier->checkAndNotify($client);

                if ($syncPerfex && $result['transaction']) {
                    $perfex->pushInvoice(
                        $client,
                        $fee,
                        'Monthly USSD Channel Maintenance ('.now()->format('M Y').')',
                        $result['transaction']
                    );
                }
            } elseif ($result['status'] === 'insufficient') {
                $summary['insufficient']++;
            } else {
                $summary['skipped']++;
            }
        }

        $summary['total_collected'] = round($summary['total_collected'], 2);

        return $summary;
    }

    /**
     * Send 20th-of-month upcoming maintenance notice emails to active clients not yet reminded this month.
     */
    public function sendUpcomingReminders(bool $forceCurrentMonth = false): int
    {
        $monthStart = Carbon::now()->startOfMonth();
        $clients = Client::query()
            ->where(fn ($q) => $q->where('is_admin', false)->orWhereNull('is_admin'))
            ->where('status', 'active')
            ->where('maintenance_enabled', true)
            ->orderBy('id')
            ->get();

        $sent = 0;
        foreach ($clients as $client) {
            $fee = round((float) ($client->monthly_maintenance_fee ?? 159500.00), 2);
            if ($fee <= 0 || ! $client->email) {
                continue;
            }

            if (! $forceCurrentMonth && $client->maintenance_reminded_at && $client->maintenance_reminded_at->gte($monthStart)) {
                continue;
            }

            try {
                Mail::to($client->email)->send(new MaintenanceReminderMail($client, $fee));
                $client->update(['maintenance_reminded_at' => now()]);
                $sent++;
            } catch (\Throwable) {
            }
        }

        return $sent;
    }
}
