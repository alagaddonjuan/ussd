<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AnnouncementMail;
use App\Models\AdminAuditLog;
use App\Models\AirtimeLog;
use App\Models\Client;
use App\Models\PricingTier;
use App\Models\ServicePrice;
use App\Models\SmsLog;
use App\Models\Transaction;
use App\Models\UssdLog;
use App\Models\UssdMenu;
use App\Models\UssdPricing;
use App\Models\WebhookLog;
use App\Services\AfricasTalkingService;
use App\Services\MaintenanceBillingService;
use App\Services\PerfexCrmService;
use App\Services\UssdEngineService;
use App\Services\UssdReconciliationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        $totalClients = Client::query()->count();
        $totalSms = SmsLog::query()->count();
        $totalUssd = UssdLog::query()->count();
        $totalRevenue = (float) Transaction::query()
            ->whereIn(DB::raw('LOWER(status)'), ['success'])
            ->where('type', 'credit')
            ->sum('amount');

        $ussdClientRevenue = (float) UssdLog::query()->sum('client_price');
        $ussdUpstreamCost = (float) UssdLog::query()->sum('upstream_cost');
        $ussdNetMargin = round($ussdClientRevenue - $ussdUpstreamCost, 2);
        $activeMaintenanceMrr = (float) Client::query()
            ->where(fn ($q) => $q->where('is_admin', false)->orWhereNull('is_admin'))
            ->where('status', 'active')
            ->where('maintenance_enabled', true)
            ->sum('monthly_maintenance_fee');
        $pendingKycCount = Client::query()
            ->where(fn ($q) => $q->where('is_admin', false)->orWhereNull('is_admin'))
            ->where('kyc_status', '!=', 'approved')
            ->count();
        $recentWebhookErrors = WebhookLog::query()
            ->whereNotIn('outcome', ['ok', 'Success', 'charge.success', 'reconciled', 'replayed:ok', 'replayed:reconciled'])
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $labels = [];
        $ussdSeries = [];
        $smsSeries = [];
        $start = Carbon::today()->subDays(6);

        $ussdCounts = UssdLog::query()
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->groupBy(fn ($row) => Carbon::parse($row->created_at)->format('Y-m-d'))
            ->map->count();

        $smsCounts = SmsLog::query()
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->groupBy(fn ($row) => Carbon::parse($row->created_at)->format('Y-m-d'))
            ->map->count();

        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $key = $day->format('Y-m-d');
            $labels[] = $day->format('M d');
            $ussdSeries[] = (int) ($ussdCounts[$key] ?? 0);
            $smsSeries[] = (int) ($smsCounts[$key] ?? 0);
        }

        $recentTransactions = Transaction::query()
            ->with('client')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('admin.dashboard', compact(
            'totalClients',
            'totalSms',
            'totalUssd',
            'totalRevenue',
            'ussdClientRevenue',
            'ussdUpstreamCost',
            'ussdNetMargin',
            'activeMaintenanceMrr',
            'pendingKycCount',
            'recentWebhookErrors',
            'labels',
            'ussdSeries',
            'smsSeries',
            'recentTransactions'
        ));
    }

    public function clients(Request $request, PerfexCrmService $perfex): View
    {
        $search = trim((string) $request->query('q', ''));

        $clients = Client::query()
            ->with('pricingTier')
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('ussd_code', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20);

        $tiers = PricingTier::query()->orderBy('tier_name')->get();
        $perfexConfigured = $perfex->isConfigured();

        return view('admin.clients', compact('clients', 'tiers', 'search', 'perfexConfigured'));
    }

    public function updateClient(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,active,suspended'],
            'ussd_code' => ['nullable', 'string', 'max:255', 'unique:clients,ussd_code,'.$client->id],
            'sender_id' => ['nullable', 'string', 'max:255'],
            'pricing_tier_id' => ['nullable', 'integer', 'exists:pricing_tiers,id'],
            'is_admin' => ['nullable', 'boolean'],
            'monthly_maintenance_fee' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'maintenance_enabled' => ['nullable', 'boolean'],
            'kyc_status' => ['nullable', 'in:unverified,pending,approved,rejected'],
            'kyc_notes' => ['nullable', 'string', 'max:1000'],
            'perfex_client_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $updates = [
            'status' => $data['status'],
            'ussd_code' => array_key_exists('ussd_code', $data) ? ($data['ussd_code'] ?: null) : $client->ussd_code,
            'sender_id' => array_key_exists('sender_id', $data) ? ($data['sender_id'] ?: null) : $client->sender_id,
            'pricing_tier_id' => array_key_exists('pricing_tier_id', $data) ? ($data['pricing_tier_id'] ?: null) : $client->pricing_tier_id,
            'is_admin' => (bool) ($data['is_admin'] ?? $client->is_admin),
        ];

        if (array_key_exists('monthly_maintenance_fee', $data) && $data['monthly_maintenance_fee'] !== null) {
            $updates['monthly_maintenance_fee'] = round((float) $data['monthly_maintenance_fee'], 2);
        }
        if ($request->has('maintenance_enabled_present')) {
            $updates['maintenance_enabled'] = (bool) ($data['maintenance_enabled'] ?? false);
        } elseif (array_key_exists('maintenance_enabled', $data)) {
            $updates['maintenance_enabled'] = (bool) $data['maintenance_enabled'];
        }
        if (! empty($data['kyc_status'])) {
            $updates['kyc_status'] = $data['kyc_status'];
        }
        if (array_key_exists('kyc_notes', $data)) {
            $updates['kyc_notes'] = $data['kyc_notes'];
        }
        if (array_key_exists('perfex_client_id', $data)) {
            $updates['perfex_client_id'] = $data['perfex_client_id'] ?: null;
        }

        $client->update($updates);
        $this->recordAudit($request, 'client.updated', "Updated {$client->name} (status={$updates['status']}, code=".($updates['ussd_code'] ?? 'none').')', $client->id);

        return back()->with('status', "Updated configuration for {$client->name}.");
    }

    public function downloadKyc(Client $client): BinaryFileResponse|RedirectResponse
    {
        if (! $client->kyc_document_path || ! Storage::disk('local')->exists($client->kyc_document_path)) {
            return back()->withErrors(['kyc' => "No KYC document file found for {$client->name}."]);
        }

        return response()->download(Storage::disk('local')->path($client->kyc_document_path));
    }

    public function pushPerfexInvoice(Request $request, Client $client, PerfexCrmService $perfex): RedirectResponse
    {
        $fee = round((float) ($client->monthly_maintenance_fee ?? 159500), 2);
        $result = $perfex->pushInvoice(
            $client,
            $fee,
            'Monthly USSD Channel Maintenance ('.now()->format('M Y').')'
        );

        $this->recordAudit($request, 'perfex.invoice_sync', "{$client->name}: {$result['message']}", $client->id);

        if (! $result['ok']) {
            return back()->withErrors(['perfex' => $result['message']]);
        }

        return back()->with('status', $result['message']);
    }

    public function chargeMaintenance(Request $request, MaintenanceBillingService $billing): RedirectResponse
    {
        $summary = $billing->billDueClients(
            $request->boolean('force'),
            $request->boolean('suspend')
        );

        $this->recordAudit(
            $request,
            'maintenance.billed',
            "Maintenance run: billed={$summary['billed']}, insufficient={$summary['insufficient']}, skipped={$summary['skipped']}, collected=₦{$summary['total_collected']}"
        );

        return back()->with('status', "Maintenance billing run complete: {$summary['billed']} billed (₦".number_format($summary['total_collected'], 2)."), {$summary['insufficient']} insufficient, {$summary['skipped']} skipped.");
    }

    public function manualTransaction(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:credit,debit'],
            'amount' => ['required', 'numeric', 'min:1', 'max:10000000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $amount = round((float) $data['amount'], 2);
        $type = $data['type'];

        $ok = DB::transaction(function () use ($client, $type, $amount, $data): bool {
            $locked = Client::query()->whereKey($client->id)->lockForUpdate()->first();
            if (! $locked) {
                return false;
            }

            if ($type === 'debit' && (float) $locked->token_balance < $amount) {
                return false;
            }

            if ($type === 'credit') {
                $locked->increment('token_balance', $amount);
            } else {
                $locked->decrement('token_balance', $amount);
            }

            Transaction::create([
                'client_id' => $locked->id,
                'type' => $type,
                'reference' => 'admin_'.$type.'_'.now()->timestamp.'_'.random_int(100, 999),
                'amount' => $amount,
                'tokens_purchased' => $type === 'credit' ? (int) round($amount) : 0,
                'status' => 'success',
                'gateway' => $type === 'credit' ? 'admin_manual' : 'admin_debit',
                'notes' => $data['notes'] ?? null,
                'created_at' => now(),
            ]);

            return true;
        });

        if (! $ok) {
            return back()->withErrors(['amount' => 'Insufficient balance for manual debit.']);
        }

        $this->recordAudit($request, 'client.wallet_'.$type, "Manual {$type} of ₦{$amount} for {$client->name}", $client->id);

        return back()->with('status', "Manual {$type} of ₦{$amount} applied to {$client->name}.");
    }

    public function pricingTiers(): View
    {
        $tiers = PricingTier::query()->with('servicePrices')->withCount('clients')->orderBy('id')->get();
        $telcoRates = UssdPricing::query()->orderBy('network_name')->get();

        return view('admin.pricing-tiers', compact('tiers', 'telcoRates'));
    }

    public function storeTier(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tier_name' => ['required', 'string', 'max:255', 'unique:pricing_tiers,tier_name'],
            'description' => ['nullable', 'string', 'max:500'],
            'sms_price' => ['required', 'numeric', 'min:0'],
            'ussd_price' => ['required', 'numeric', 'min:0'],
            'airtime_price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data): void {
            $tier = PricingTier::create([
                'tier_name' => $data['tier_name'],
                'description' => $data['description'] ?? null,
            ]);

            ServicePrice::insert([
                ['pricing_tier_id' => $tier->id, 'service_name' => 'SMS', 'price' => $data['sms_price']],
                ['pricing_tier_id' => $tier->id, 'service_name' => 'USSD', 'price' => $data['ussd_price']],
                ['pricing_tier_id' => $tier->id, 'service_name' => 'AIRTIME', 'price' => $data['airtime_price']],
            ]);
        });

        return back()->with('status', "Pricing tier \"{$data['tier_name']}\" created.");
    }

    public function updateTierPrices(Request $request, PricingTier $tier): RedirectResponse
    {
        $data = $request->validate([
            'sms_price' => ['required', 'numeric', 'min:0'],
            'ussd_price' => ['required', 'numeric', 'min:0'],
            'airtime_price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($tier, $data): void {
            foreach (['SMS' => $data['sms_price'], 'USSD' => $data['ussd_price'], 'AIRTIME' => $data['airtime_price']] as $service => $price) {
                ServicePrice::updateOrCreate(
                    ['pricing_tier_id' => $tier->id, 'service_name' => $service],
                    ['price' => $price]
                );
            }
        });

        return back()->with('status', "Updated service prices for {$tier->tier_name}.");
    }

    public function destroyTier(PricingTier $tier): RedirectResponse
    {
        if ($tier->clients()->exists()) {
            return back()->withErrors(['tier' => 'Cannot delete tier while clients are assigned to it.']);
        }

        $tier->delete();

        return back()->with('status', "Deleted pricing tier \"{$tier->tier_name}\".");
    }

    public function storeTelcoRate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'network_code' => ['required', 'string', 'max:50'],
            'network_name' => ['required', 'string', 'max:255'],
            'tokens_per_interval' => ['required', 'integer', 'min:0'],
        ]);

        UssdPricing::updateOrCreate(
            ['network_code' => $data['network_code']],
            [
                'network_name' => $data['network_name'],
                'tokens_per_interval' => $data['tokens_per_interval'],
            ]
        );

        return back()->with('status', "Saved telco USSD rate for {$data['network_name']}.");
    }

    public function logs(Request $request, UssdEngineService $engine): View
    {
        $data = $this->buildLogsData($request, $engine);

        return view('admin.logs', $data);
    }

    public function liveLogs(Request $request, UssdEngineService $engine): JsonResponse
    {
        $data = $this->buildLogsData($request, $engine);
        $html = view('admin.partials._logs-table', $data)->render();
        $summary = $data['ussdSummary'];

        return response()->json([
            'html' => $html,
            'summary' => [
                'total_sessions' => number_format($summary['total_sessions']),
                'total_billed' => '₦'.number_format($summary['total_billed'], 2),
                'total_upstream' => '₦'.number_format($summary['total_upstream'], 2),
                'total_margin' => '₦'.number_format($summary['total_margin'], 2),
                'unreconciled_count' => number_format($summary['unreconciled_count']),
                'zero_cost_count' => number_format($summary['zero_cost_count']),
                'failed_count' => number_format($summary['failed_count']),
                'webhook_failures' => number_format($summary['webhook_failures']),
            ],
            'counts' => $data['tabCounts'],
            'updated_at' => now()->format('H:i:s'),
        ]);
    }

    public function atBalance(AfricasTalkingService $at): JsonResponse
    {
        return response()->json($at->getApplicationBalance());
    }

    public function reconcileAtCsv(Request $request, UssdReconciliationService $reconciler): RedirectResponse
    {
        $data = $request->validate([
            'csv_file' => ['nullable', 'file', 'mimes:csv,txt', 'max:5120'],
            'csv_text' => ['nullable', 'string', 'max:500000'],
            'action' => ['required', 'in:preview,apply'],
        ]);

        $raw = '';
        if ($request->hasFile('csv_file')) {
            $raw = (string) file_get_contents($request->file('csv_file')->getRealPath());
        } elseif (! empty($data['csv_text'])) {
            $raw = (string) $data['csv_text'];
        }

        if (trim($raw) === '') {
            return redirect()->route('admin.logs', ['tab' => 'reconcile'])
                ->withErrors(['reconcile' => 'Upload an Africa\'s Talking CSV export or paste session rows to verify.']);
        }

        $parsedRows = $reconciler->parseInput($raw);
        if (empty($parsedRows)) {
            return redirect()->route('admin.logs', ['tab' => 'reconcile'])
                ->withErrors(['reconcile' => 'Could not detect any valid USSD session rows in the input.']);
        }

        if ($data['action'] === 'apply') {
            $summary = $reconciler->apply($parsedRows);
            $this->recordAudit(
                $request,
                'ussd.reconcile_csv',
                "AT CSV reconciliation: created={$summary['created']}, updated={$summary['updated']}, skipped={$summary['skipped']}, charged=₦{$summary['total_delta_charged']}"
            );

            return redirect()->route('admin.logs', ['tab' => 'ussd'])
                ->with('status', "Africa's Talking verification applied: {$summary['created']} missing session(s) backfilled, {$summary['updated']} session(s) reconciled, ₦".number_format($summary['total_delta_charged'], 2).' net billed.');
        }

        $analysis = $reconciler->analyze($parsedRows);
        $previewItems = array_map(function (array $item) {
            return [
                'at' => $item['at'],
                'client_name' => $item['client']?->name ?? 'Unknown Client',
                'multiplier' => $item['client']?->getUssdCostMultiplier(),
                'log_id' => $item['log']?->id,
                'log_session_id' => $item['log']?->session_id,
                'state' => $item['state'],
                'expected_billed' => $item['expected_billed'],
                'local_upstream' => $item['local_upstream'],
                'local_billed' => $item['local_billed'],
                'delta' => $item['delta'],
            ];
        }, $analysis['items']);

        return redirect()->route('admin.logs', ['tab' => 'reconcile'])
            ->with('at_reconcile_preview', [
                'totals' => $analysis['totals'],
                'items' => $previewItems,
                'raw_csv' => $raw,
            ]);
    }

    public function reconcileSingleUssdLog(
        Request $request,
        UssdLog $ussdLog,
        UssdReconciliationService $reconciler
    ): RedirectResponse {
        $data = $request->validate([
            'at_cost' => ['required', 'numeric', 'min:0', 'max:100000'],
            'status' => ['nullable', 'string', 'max:50'],
            'duration_s' => ['nullable', 'numeric', 'min:0', 'max:3600'],
        ]);

        $durationMs = isset($data['duration_s']) && $data['duration_s'] !== null && $data['duration_s'] !== ''
            ? (int) round(((float) $data['duration_s']) * 1000)
            : null;

        $res = $reconciler->reconcileSingleLog(
            $ussdLog,
            (float) $data['at_cost'],
            $data['status'] ?: null,
            $durationMs
        );

        $this->recordAudit(
            $request,
            'ussd.reconcile_single',
            "Reconciled USSD log #{$ussdLog->id} ({$ussdLog->session_id}): AT=₦{$res['at_cost']}, Ours=₦{$res['client_charge']}, Delta=₦{$res['delta']}",
            $ussdLog->client_id
        );

        return back()->with('status', "Reconciled session #{$ussdLog->id}: AT cost ₦".number_format($res['at_cost'], 2).', billed client ₦'.number_format($res['client_charge'], 2).'.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildLogsData(Request $request, UssdEngineService $engine): array
    {
        $tab = (string) $request->query('tab', 'ussd');
        $search = trim((string) $request->query('q', ''));
        $statusFilter = trim((string) $request->query('status', 'all'));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $ussdBaseQuery = UssdLog::query()
            ->with('client')
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('phone_number', 'like', "%{$search}%")
                        ->orWhere('session_id', 'like', "%{$search}%")
                        ->orWhere('service_code', 'like', "%{$search}%")
                        ->orWhere('gateway_error', 'like', "%{$search}%")
                        ->orWhereHas('client', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($dateFrom !== '', fn ($q) => $q->where('created_at', '>=', "{$dateFrom} 00:00:00"))
            ->when($dateTo !== '', fn ($q) => $q->where('created_at', '<=', "{$dateTo} 23:59:59"));

        $filteredSummaryQuery = clone $ussdBaseQuery;
        $this->applyUssdStatusFilter($ussdBaseQuery, $statusFilter);

        $ussdLogs = (clone $ussdBaseQuery)
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $ussdLogs->getCollection()->transform(function (UssdLog $log) use ($engine) {
            $log->estimated_at_cost = $engine->estimateAtUpstreamCost('', (string) ($log->network_name ?: $log->phone_number));

            return $log;
        });

        $summaryRow = (clone $filteredSummaryQuery)
            ->selectRaw('COUNT(*) as total_sessions')
            ->selectRaw('COALESCE(SUM(COALESCE(client_price, cost, 0)), 0) as total_billed')
            ->selectRaw('COALESCE(SUM(COALESCE(upstream_cost, 0)), 0) as total_upstream')
            ->selectRaw('SUM(CASE WHEN reconciled_at IS NULL AND COALESCE(upstream_cost, 0) <= 0 THEN 1 ELSE 0 END) as unreconciled_count')
            ->selectRaw('SUM(CASE WHEN COALESCE(client_price, cost, 0) <= 0 THEN 1 ELSE 0 END) as zero_cost_count')
            ->selectRaw("SUM(CASE WHEN LOWER(COALESCE(status, '')) IN ('failed', 'rejected', 'incomplete') OR gateway_error IS NOT NULL THEN 1 ELSE 0 END) as failed_count")
            ->first();

        $totalBilled = round((float) ($summaryRow->total_billed ?? 0), 2);
        $totalUpstream = round((float) ($summaryRow->total_upstream ?? 0), 2);
        $webhookFailures = WebhookLog::query()
            ->whereNotIn('outcome', ['ok', 'Success', 'charge.success', 'reconciled', 'replayed:ok', 'replayed:reconciled'])
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $ussdSummary = [
            'total_sessions' => (int) ($summaryRow->total_sessions ?? 0),
            'total_billed' => $totalBilled,
            'total_upstream' => $totalUpstream,
            'total_margin' => round($totalBilled - $totalUpstream, 2),
            'unreconciled_count' => (int) ($summaryRow->unreconciled_count ?? 0),
            'zero_cost_count' => (int) ($summaryRow->zero_cost_count ?? 0),
            'failed_count' => (int) ($summaryRow->failed_count ?? 0),
            'webhook_failures' => $webhookFailures,
        ];

        $smsLogs = SmsLog::query()->with('client')->orderByDesc('id')->limit(50)->get();
        $airtimeLogs = AirtimeLog::query()->with('client')->orderByDesc('id')->limit(50)->get();
        $transactions = Transaction::query()->with('client')->orderByDesc('id')->limit(50)->get();
        $webhookLogs = WebhookLog::query()->orderByDesc('id')->limit(50)->get();
        $auditLogs = AdminAuditLog::query()->with(['actor', 'targetClient'])->orderByDesc('id')->limit(50)->get();

        $tabCounts = [
            'ussd' => $ussdLogs->total(),
            'sms' => $smsLogs->count(),
            'airtime' => $airtimeLogs->count(),
            'transactions' => $transactions->count(),
            'webhooks' => $webhookLogs->count(),
            'audit' => $auditLogs->count(),
        ];

        return compact(
            'tab',
            'search',
            'statusFilter',
            'dateFrom',
            'dateTo',
            'ussdLogs',
            'ussdSummary',
            'smsLogs',
            'airtimeLogs',
            'transactions',
            'webhookLogs',
            'auditLogs',
            'tabCounts'
        );
    }

    protected function applyUssdStatusFilter($query, string $statusFilter): void
    {
        match ($statusFilter) {
            'unreconciled' => $query->whereNull('reconciled_at')->where(fn ($q) => $q->whereNull('upstream_cost')->orWhere('upstream_cost', '<=', 0)),
            'zero_cost' => $query->whereRaw('COALESCE(client_price, cost, 0) <= 0'),
            'incomplete' => $query->whereRaw("LOWER(COALESCE(status, '')) = 'incomplete'"),
            'failed' => $query->where(fn ($q) => $q->whereRaw("LOWER(COALESCE(status, '')) IN ('failed', 'rejected')")->orWhereNotNull('gateway_error')),
            'success' => $query->whereRaw("LOWER(COALESCE(status, '')) = 'success'"),
            default => null,
        };
    }

    public function replayWebhook(Request $request, WebhookLog $webhookLog, UssdEngineService $engine): RedirectResponse
    {
        $payload = json_decode((string) $webhookLog->payload, true);
        if (! is_array($payload)) {
            return back()->withErrors(['webhook' => 'Stored webhook payload is not valid JSON for replay.']);
        }

        if ($webhookLog->provider !== 'ussd_gateway') {
            return back()->withErrors(['webhook' => 'Only internal USSD gateway events can be replayed safely without gateway re-signing.']);
        }

        $start = microtime(true);
        $outcome = 'replayed';
        if (str_contains((string) $webhookLog->endpoint, 'events')) {
            $res = $engine->reconcileEventCost($payload);
            $outcome = ($res['ok'] ?? true) ? 'replayed:reconciled' : ('replayed:'.($res['outcome'] ?? 'error'));
        } else {
            $reply = $engine->handleCallback($payload, true);
            $outcome = str_starts_with($reply, 'END Error:') ? 'replayed:rejected' : 'replayed:ok';
        }

        WebhookLog::create([
            'provider' => $webhookLog->provider,
            'endpoint' => $webhookLog->endpoint,
            'status_code' => 200,
            'latency_ms' => (int) round((microtime(true) - $start) * 1000),
            'outcome' => $outcome,
            'payload' => $webhookLog->payload,
            'created_at' => now(),
        ]);

        $this->recordAudit($request, 'webhook.replayed', "Replayed webhook #{$webhookLog->id} ({$webhookLog->endpoint}) -> {$outcome}");

        return back()->with('status', "Replayed webhook #{$webhookLog->id} ({$outcome}).");
    }

    public function exportCsv(Request $request, string $type): StreamedResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = match ($type) {
            'sms' => SmsLog::query(),
            'airtime' => AirtimeLog::query(),
            'ussd' => UssdLog::query(),
            default => abort(404),
        };

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', ["{$startDate} 00:00:00", "{$endDate} 23:59:59"]);
        } else {
            $query->limit(5000);
        }

        $rows = $query->orderByDesc('id')->get();
        $filename = "admin_{$type}_report_".now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            if ($rows->isNotEmpty()) {
                fputcsv($out, array_keys($rows->first()->toArray()));
                foreach ($rows as $row) {
                    fputcsv($out, array_values($row->toArray()));
                }
            } else {
                fputcsv($out, ['No records found']);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function ussdMenus(): View
    {
        $menus = UssdMenu::query()
            ->with('client')
            ->withCount('items')
            ->orderByDesc('id')
            ->get();

        return view('admin.ussd-menus', compact('menus'));
    }

    public function announcements(): View
    {
        $activeCount = Client::query()->where('status', 'active')->count();

        return view('admin.announcements', compact('activeCount'));
    }

    public function sendAnnouncement(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $emails = Client::query()->where('status', 'active')->pluck('email');

        foreach ($emails as $email) {
            Mail::to($email)->send(new AnnouncementMail($data['subject'], $data['message']));
        }

        $this->recordAudit($request, 'announcement.sent', "Announcement '{$data['subject']}' sent to {$emails->count()} client(s)");

        return back()->with('status', "Announcement dispatched to {$emails->count()} active client(s).");
    }

    protected function recordAudit(Request $request, string $action, string $details, ?int $targetClientId = null): void
    {
        try {
            AdminAuditLog::create([
                'actor_id' => $request->user()?->id,
                'target_client_id' => $targetClientId,
                'action' => $action,
                'details' => $details,
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
        }
    }
}
