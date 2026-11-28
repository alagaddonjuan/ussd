@extends('layout.main')
@section('title', 'System Logs & AT Cost Reconciliation — ' . config('app.name'))

@section('content')
@php
    $reconcilePreview = session('at_reconcile_preview');
@endphp
<div class="main-inner">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 lg:mb-8">
        <div>
            <h2 class="h2">Platform System Logs</h2>
            <p class="text-sm text-n100 mt-1">Session-level Africa's Talking cost comparison, failure diagnostics, and live refresh</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <!-- Live Refresh Controls (no browser reload) -->
            <div class="flex flex-wrap items-center gap-2 rounded-3xl border border-n30 bg-n0 px-3 py-1.5">
                <span class="inline-flex items-center gap-1.5 text-xs font-medium">
                    <span id="admin-live-dot" class="inline-block size-2 rounded-full bg-[#10B981]"></span>
                    <span id="admin-live-status">Updated {{ now()->format('H:i:s') }}</span>
                </span>
                <select id="admin-auto-refresh-select" aria-label="Auto refresh interval"
                    class="rounded-2xl border border-n30 bg-primary/5 px-2 py-1 text-xs font-medium">
                    <option value="0">Auto: Off</option>
                    <option value="5000">Auto: 5s</option>
                    <option value="10000" selected>Auto: 10s</option>
                    <option value="30000">Auto: 30s</option>
                </select>
                <button type="button" id="admin-logs-refresh-btn" class="btn-primary py-1 px-3 text-xs">
                    <i class="las la-sync" id="admin-logs-refresh-icon"></i>
                    <span>Refresh</span>
                </button>
            </div>

            <a href="{{ route('admin.reports.export', 'ussd') }}" class="btn-outline py-2 px-4 text-xs"><i class="las la-download"></i> USSD CSV</a>
            <a href="{{ route('admin.reports.export', 'sms') }}" class="btn-outline py-2 px-4 text-xs"><i class="las la-download"></i> SMS CSV</a>
            <a href="{{ route('admin.reports.export', 'airtime') }}" class="btn-outline py-2 px-4 text-xs"><i class="las la-download"></i> Airtime CSV</a>
        </div>
    </div>

    <!-- AT Cost vs Ours Comparison KPI Strip -->
    <div class="grid grid-cols-12 gap-4 mb-6">
        <div class="box col-span-12 sm:col-span-6 xl:col-span-3 border border-n30 border-t-2 border-t-[#0878F8]">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-medium text-n100">USSD Sessions (Filtered)</span>
                <i class="las la-phone-volume text-lg text-[#0878F8]"></i>
            </div>
            <h4 class="h4" id="kpi-total-sessions">{{ number_format($ussdSummary['total_sessions']) }}</h4>
            <p class="text-[11px] text-n100 mt-1">1 row per session (consolidated)</p>
        </div>

        <div class="box col-span-12 sm:col-span-6 xl:col-span-3 border border-n30 border-t-2 border-t-[#F59E0B]">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-medium text-n100">Africa's Talking Cost</span>
                <button type="button" id="check-at-balance-btn" class="text-[11px] font-semibold text-primary hover:underline">
                    Check AT Wallet
                </button>
            </div>
            <h4 class="h4" id="kpi-total-upstream">₦{{ number_format($ussdSummary['total_upstream'], 2) }}</h4>
            <p class="text-[11px] text-n100 mt-1" id="at-wallet-balance-text">Upstream telco cost billed by AT</p>
        </div>

        <div class="box col-span-12 sm:col-span-6 xl:col-span-3 border border-n30 border-t-2 border-t-[#0B3A75]">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-medium text-n100">Our Billed Revenue</span>
                <i class="las la-coins text-lg text-[#0B3A75] dark:text-[#22C7F8]"></i>
            </div>
            <h4 class="h4" id="kpi-total-billed">₦{{ number_format($ussdSummary['total_billed'], 2) }}</h4>
            <p class="text-[11px] text-[#10B981] font-semibold mt-1">
                Net Margin: <span id="kpi-total-margin">₦{{ number_format($ussdSummary['total_margin'], 2) }}</span>
            </p>
        </div>

        <div class="box col-span-12 sm:col-span-6 xl:col-span-3 border border-n30 border-t-2 border-t-error">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-medium text-n100">Verification Alerts</span>
                <i class="las la-exclamation-circle text-lg text-error"></i>
            </div>
            <div class="flex flex-wrap gap-1.5 mt-1.5 text-[11px] font-semibold">
                <a href="{{ route('admin.logs', ['tab' => 'ussd', 'status' => 'unreconciled']) }}"
                    class="rounded-full bg-[#F59E0B]/15 text-[#F59E0B] px-2.5 py-0.5 hover:opacity-80">
                    Unreconciled: <span id="kpi-unreconciled">{{ number_format($ussdSummary['unreconciled_count']) }}</span>
                </a>
                <a href="{{ route('admin.logs', ['tab' => 'ussd', 'status' => 'zero_cost']) }}"
                    class="rounded-full bg-error/15 text-error px-2.5 py-0.5 hover:opacity-80">
                    ₦0.00: <span id="kpi-zero-cost">{{ number_format($ussdSummary['zero_cost_count']) }}</span>
                </a>
                <a href="{{ route('admin.logs', ['tab' => 'ussd', 'status' => 'failed']) }}"
                    class="rounded-full bg-error/15 text-error px-2.5 py-0.5 hover:opacity-80">
                    Issues: <span id="kpi-failed">{{ number_format($ussdSummary['failed_count']) }}</span>
                </a>
                <a href="{{ route('admin.logs', ['tab' => 'webhooks']) }}"
                    class="rounded-full bg-secondary/15 text-secondary px-2.5 py-0.5 hover:opacity-80">
                    24h WH Err: <span id="kpi-webhook-failures">{{ number_format($ussdSummary['webhook_failures']) }}</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Gateway Callback & Events Diagnostic Strip -->
    <div class="mb-6 rounded-2xl border border-primary/25 bg-primary/5 px-4 py-3 flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex flex-wrap items-center gap-3">
            <span class="font-semibold text-primary"><i class="las la-plug"></i> Africa's Talking Endpoints:</span>
            <span>Callback: <code class="font-mono font-semibold text-n200 dark:text-white">{{ url('/ussd') }}</code></span>
            <span>Events URL: <code class="font-mono font-semibold text-n200 dark:text-white">{{ url('/ussd/events') }}</code></span>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-n100">Missing sessions or ₦0.00 rows?</span>
            <a href="{{ route('admin.logs', ['tab' => 'reconcile']) }}" class="btn-secondary py-1 px-3 text-xs">
                <i class="las la-balance-scale"></i> Verify Against AT CSV
            </a>
        </div>
    </div>

    <div class="box border border-n30">
        <div class="tab-navigation mb-6 flex-wrap items-center gap-x-3 gap-y-2 pb-1">
            <a href="{{ route('admin.logs', ['tab' => 'ussd']) }}"
                class="tab-link inline-flex items-center gap-1.5 whitespace-nowrap shrink-0 text-sm {{ $tab === 'ussd' ? 'active' : '' }}">
                <span>USSD Sessions</span>
                <span id="tab-count-ussd" class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-semibold text-primary">{{ $tabCounts['ussd'] }}</span>
            </a>
            <a href="{{ route('admin.logs', ['tab' => 'reconcile']) }}"
                class="tab-link inline-flex items-center gap-1.5 whitespace-nowrap shrink-0 text-sm {{ $tab === 'reconcile' ? 'active' : '' }}">
                <span>Verify vs AT (CSV)</span>
            </a>
            <a href="{{ route('admin.logs', ['tab' => 'sms']) }}"
                class="tab-link inline-flex items-center gap-1.5 whitespace-nowrap shrink-0 text-sm {{ $tab === 'sms' ? 'active' : '' }}">
                <span>SMS Logs</span>
                <span id="tab-count-sms" class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-semibold text-primary">{{ $tabCounts['sms'] }}</span>
            </a>
            <a href="{{ route('admin.logs', ['tab' => 'airtime']) }}"
                class="tab-link inline-flex items-center gap-1.5 whitespace-nowrap shrink-0 text-sm {{ $tab === 'airtime' ? 'active' : '' }}">
                <span>Airtime Logs</span>
                <span id="tab-count-airtime" class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-semibold text-primary">{{ $tabCounts['airtime'] }}</span>
            </a>
            <a href="{{ route('admin.logs', ['tab' => 'transactions']) }}"
                class="tab-link inline-flex items-center gap-1.5 whitespace-nowrap shrink-0 text-sm {{ $tab === 'transactions' ? 'active' : '' }}">
                <span>Transactions</span>
                <span id="tab-count-transactions" class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-semibold text-primary">{{ $tabCounts['transactions'] }}</span>
            </a>
            <a href="{{ route('admin.logs', ['tab' => 'webhooks']) }}"
                class="tab-link inline-flex items-center gap-1.5 whitespace-nowrap shrink-0 text-sm {{ $tab === 'webhooks' ? 'active' : '' }}">
                <span>Webhooks</span>
                <span id="tab-count-webhooks" class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-semibold text-primary">{{ $tabCounts['webhooks'] }}</span>
            </a>
            <a href="{{ route('admin.logs', ['tab' => 'audit']) }}"
                class="tab-link inline-flex items-center gap-1.5 whitespace-nowrap shrink-0 text-sm {{ $tab === 'audit' ? 'active' : '' }}">
                <span>Audit Trail</span>
                <span id="tab-count-audit" class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-semibold text-primary">{{ $tabCounts['audit'] }}</span>
            </a>
        </div>

        @if ($tab === 'reconcile')
            <div class="space-y-6">
                <div class="rounded-2xl border border-n30 bg-primary/5 p-5">
                    <h4 class="h4 mb-1">Verify &amp; Reconcile Sessions Against Africa's Talking</h4>
                    <p class="text-xs text-n100 mb-4">
                        Export your USSD sessions CSV from the Africa's Talking dashboard (or copy-paste rows directly from the AT USSD Sessions table) to detect any missing sessions, ₦0.00 unbilled entries, or cost discrepancies and charge clients accurately.
                    </p>

                    <form method="POST" action="{{ route('admin.logs.reconcile') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-12 md:col-span-4">
                                <label for="csv_file" class="block text-xs font-semibold mb-1.5">Upload AT CSV Export (.csv / .txt)</label>
                                <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt"
                                    class="w-full rounded-2xl border border-n30 bg-n0 px-3 py-2 text-xs" />
                            </div>
                            <div class="col-span-12 md:col-span-8">
                                <label for="csv_text" class="block text-xs font-semibold mb-1.5">Or Paste AT Session Rows (CSV or Tab-separated from AT Dashboard)</label>
                                <textarea name="csv_text" id="csv_text" rows="5"
                                    class="w-full rounded-2xl border border-n30 bg-n0 px-3 py-2 text-xs font-mono"
                                    placeholder="Date,Service Code,Phone Number,Hops,Duration,Cost,Status&#10;November 27, 2026 11:02 AM,*347*102#,+2348034202984,4,21s,NGN 15.0000,Success">{{ old('csv_text', $reconcilePreview['raw_csv'] ?? '') }}</textarea>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button type="submit" name="action" value="preview" class="btn-primary py-2 px-5 text-xs">
                                <i class="las la-search"></i> Preview &amp; Compare
                            </button>
                            <button type="submit" name="action" value="apply" class="btn-secondary py-2 px-5 text-xs"
                                onclick="return confirm('This will backfill missing sessions, update mismatched costs, and debit client wallets for any unbilled amounts. Continue?')">
                                <i class="las la-check-double"></i> Reconcile &amp; Charge Missing / Mismatched
                            </button>
                        </div>
                    </form>
                </div>

                @if (is_array($reconcilePreview))
                    @php $t = $reconcilePreview['totals']; @endphp
                    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-3">
                        <div class="rounded-xl border border-n30 p-3">
                            <div class="text-[11px] text-n100">AT Rows Parsed</div>
                            <div class="text-lg font-bold">{{ $t['at_rows'] }}</div>
                        </div>
                        <div class="rounded-xl border border-n30 p-3">
                            <div class="text-[11px] text-n100">AT Total Cost</div>
                            <div class="text-lg font-bold text-[#F59E0B]">₦{{ number_format($t['at_cost'], 2) }}</div>
                        </div>
                        <div class="rounded-xl border border-n30 p-3">
                            <div class="text-[11px] text-n100">Expected Client Charge</div>
                            <div class="text-lg font-bold text-primary">₦{{ number_format($t['expected_billed'], 2) }}</div>
                        </div>
                        <div class="rounded-xl border border-n30 p-3">
                            <div class="text-[11px] text-n100">Currently Recorded</div>
                            <div class="text-lg font-bold">₦{{ number_format($t['local_billed'], 2) }}</div>
                        </div>
                        <div class="rounded-xl border border-n30 p-3">
                            <div class="text-[11px] text-n100">Missing / Mismatched</div>
                            <div class="text-lg font-bold text-error">{{ $t['missing_local'] }} missing / {{ $t['mismatch'] }} diff</div>
                        </div>
                        <div class="rounded-xl border border-n30 p-3">
                            <div class="text-[11px] text-n100">Unbilled Shortfall</div>
                            <div class="text-lg font-bold text-error">₦{{ number_format($t['unbilled_delta'], 2) }}</div>
                        </div>
                    </div>

                    @if (($t['missing_local'] + $t['mismatch']) > 0)
                        <form method="POST" action="{{ route('admin.logs.reconcile') }}" class="flex items-center justify-between rounded-2xl border border-[#F59E0B]/40 bg-[#F59E0B]/10 px-4 py-3">
                            @csrf
                            <input type="hidden" name="csv_text" value="{{ $reconcilePreview['raw_csv'] }}" />
                            <input type="hidden" name="action" value="apply" />
                            <span class="text-xs font-semibold">
                                Found {{ $t['missing_local'] }} missing session(s) and {{ $t['mismatch'] }} cost mismatch(es) totaling ₦{{ number_format($t['unbilled_delta'], 2) }} unbilled.
                            </span>
                            <button type="submit" class="btn-primary py-1.5 px-4 text-xs"
                                onclick="return confirm('Apply reconciliation and charge client wallets for missing/mismatched sessions?')">
                                Apply &amp; Charge All
                            </button>
                        </form>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="w-full whitespace-nowrap text-xs">
                            <thead>
                                <tr class="bg-secondary/5">
                                    <th class="px-3 py-2.5 text-start">AT Date</th>
                                    <th class="px-3 py-2.5 text-start">Code &amp; Phone</th>
                                    <th class="px-3 py-2.5 text-start">Hops / Dur.</th>
                                    <th class="px-3 py-2.5 text-start">AT Cost</th>
                                    <th class="px-3 py-2.5 text-start">Expected Ours</th>
                                    <th class="px-3 py-2.5 text-start">Local Recorded</th>
                                    <th class="px-3 py-2.5 text-start">Delta</th>
                                    <th class="px-3 py-2.5 text-start">Match Result</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($reconcilePreview['items'] as $item)
                                    @php
                                        $at = $item['at'];
                                        $stBadge = match ($item['state']) {
                                            'matched_ok' => 'bg-[#10B981]/15 text-[#10B981]',
                                            'mismatch' => 'bg-[#F59E0B]/15 text-[#F59E0B]',
                                            'missing_local' => 'bg-error/15 text-error',
                                            default => 'bg-secondary/15 text-secondary',
                                        };
                                        $stLabel = match ($item['state']) {
                                            'matched_ok' => 'Matched OK',
                                            'mismatch' => 'Mismatch / Unreconciled',
                                            'missing_local' => 'MISSING IN Q4I',
                                            default => 'Unknown Service Code',
                                        };
                                    @endphp
                                    <tr class="even:bg-secondary/5">
                                        <td class="px-3 py-2 text-n100">{{ $at['raw_date'] ?: '—' }}</td>
                                        <td class="px-3 py-2">
                                            <div class="font-medium">{{ $at['phone_number'] }}</div>
                                            <div class="text-[11px] font-mono text-n100">{{ $at['service_code'] }} ({{ $item['client_name'] }})</div>
                                        </td>
                                        <td class="px-3 py-2">{{ $at['hops'] }} / {{ $at['duration_ms'] !== null ? round($at['duration_ms'] / 1000) . 's' : '—' }}</td>
                                        <td class="px-3 py-2 font-semibold">₦{{ number_format($at['at_cost'], 2) }}</td>
                                        <td class="px-3 py-2 font-semibold">₦{{ number_format($item['expected_billed'], 2) }}</td>
                                        <td class="px-3 py-2">
                                            @if ($item['log_id'])
                                                <div>Billed: ₦{{ number_format($item['local_billed'], 2) }}</div>
                                                <div class="text-[11px] text-n100">AT: ₦{{ number_format($item['local_upstream'], 2) }} (#{{ $item['log_id'] }})</div>
                                            @else
                                                <span class="text-error font-semibold">Not recorded</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 font-semibold {{ $item['delta'] > 0 ? 'text-error' : 'text-[#10B981]' }}">
                                            {{ $item['delta'] > 0 ? '+' : '' }}₦{{ number_format($item['delta'], 2) }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="rounded-full px-2.5 py-0.5 font-semibold {{ $stBadge }}">{{ $stLabel }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @else
            @if ($tab === 'ussd' || !in_array($tab, ['sms', 'airtime', 'transactions', 'webhooks', 'audit'], true))
                <form method="GET" action="{{ route('admin.logs') }}" id="ussd-filter-form" class="mb-4 grid grid-cols-12 gap-2 items-end">
                    <input type="hidden" name="tab" value="ussd" />
                    <div class="col-span-12 md:col-span-4">
                        <label class="block text-[11px] font-medium text-n100 mb-1">Search phone, session ID, client, error</label>
                        <input type="text" name="q" value="{{ $search }}" placeholder="+234..., ATUid_..., Modern Lottery"
                            class="w-full rounded-2xl border border-n30 bg-primary/5 px-3 py-2 text-xs" />
                    </div>
                    <div class="col-span-6 md:col-span-3">
                        <label class="block text-[11px] font-medium text-n100 mb-1">Session Filter</label>
                        <select name="status" class="w-full rounded-2xl border border-n30 bg-primary/5 px-3 py-2 text-xs">
                            <option value="all" @selected($statusFilter === 'all')>All Sessions</option>
                            <option value="unreconciled" @selected($statusFilter === 'unreconciled')>Awaiting / Unreconciled AT Event</option>
                            <option value="zero_cost" @selected($statusFilter === 'zero_cost')>₦0.00 Billed Only</option>
                            <option value="incomplete" @selected($statusFilter === 'incomplete')>Incomplete</option>
                            <option value="failed" @selected($statusFilter === 'failed')>Failed / Rejected / Error</option>
                            <option value="success" @selected($statusFilter === 'success')>Success</option>
                        </select>
                    </div>
                    <div class="col-span-6 md:col-span-2">
                        <label class="block text-[11px] font-medium text-n100 mb-1">From Date</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}"
                            class="w-full rounded-2xl border border-n30 bg-primary/5 px-3 py-2 text-xs" />
                    </div>
                    <div class="col-span-6 md:col-span-2">
                        <label class="block text-[11px] font-medium text-n100 mb-1">To Date</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}"
                            class="w-full rounded-2xl border border-n30 bg-primary/5 px-3 py-2 text-xs" />
                    </div>
                    <div class="col-span-6 md:col-span-1 flex gap-1.5">
                        <button type="submit" class="btn-primary py-2 px-3 text-xs w-full justify-center">Go</button>
                    </div>
                </form>
            @endif

            <div class="overflow-x-auto" id="admin-logs-table-container">
                @include('admin.partials._logs-table')
            </div>
        @endif
    </div>
</div>
@endsection

@section('page-modal')
<div id="single-reconcile-modal" class="hidden fixed inset-0 z-[70] flex items-center justify-center bg-black/50 p-4">
    <div class="box w-full max-w-md border border-n30 bg-n0 p-6">
        <div class="flex items-center justify-between bb-dashed pb-3 mb-4">
            <h5 class="h5">Reconcile USSD Session</h5>
            <button type="button" id="close-reconcile-modal" class="text-n100 hover:text-error">
                <i class="las la-times text-xl"></i>
            </button>
        </div>
        <form method="POST" id="single-reconcile-form" action="" class="space-y-4">
            @csrf
            <div class="rounded-xl bg-primary/5 p-3 text-xs space-y-1">
                <div><strong>Session:</strong> <span id="rec-modal-session" class="font-mono"></span></div>
                <div><strong>Phone:</strong> <span id="rec-modal-phone"></span></div>
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">Africa's Talking Cost (NGN)</label>
                <input type="number" name="at_cost" id="rec-modal-cost" step="0.01" min="0" required
                    class="w-full rounded-2xl border border-n30 bg-primary/5 px-3 py-2 text-sm" />
                <p class="text-[11px] text-n100 mt-1">Client wallet will be charged AT Cost × client tier multiplier (minus any amount already billed).</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium mb-1">Status</label>
                    <select name="status" id="rec-modal-status" class="w-full rounded-2xl border border-n30 bg-primary/5 px-3 py-2 text-xs">
                        <option value="Success">Success</option>
                        <option value="Incomplete">Incomplete</option>
                        <option value="Failed">Failed</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Duration (seconds)</label>
                    <input type="number" name="duration_s" id="rec-modal-duration" min="0"
                        class="w-full rounded-2xl border border-n30 bg-primary/5 px-3 py-2 text-xs" placeholder="Optional" />
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" id="cancel-reconcile-modal" class="btn-outline py-2 px-4 text-xs">Cancel</button>
                <button type="submit" class="btn-primary py-2 px-4 text-xs">Reconcile &amp; Charge</button>
            </div>
        </form>
    </div>
</div>
@include('partials._ussd-session-details-modal')
@endsection

@push('page-js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const refreshBtn = document.getElementById('admin-logs-refresh-btn');
    const refreshIcon = document.getElementById('admin-logs-refresh-icon');
    const intervalSelect = document.getElementById('admin-auto-refresh-select');
    const statusLabel = document.getElementById('admin-live-status');
    const liveDot = document.getElementById('admin-live-dot');
    const tableContainer = document.getElementById('admin-logs-table-container');
    const checkAtBalanceBtn = document.getElementById('check-at-balance-btn');
    const atBalanceText = document.getElementById('at-wallet-balance-text');
    const modal = document.getElementById('single-reconcile-modal');
    const recForm = document.getElementById('single-reconcile-form');

    const storageKey = 'q4_admin_logs_refresh_ms';
    const currentTab = @json($tab);
    let timerId = null;
    let isFetching = false;

    function bindModalTriggers() {
        document.querySelectorAll('.js-open-reconcile').forEach((btn) => {
            btn.addEventListener('click', () => {
                if (!modal || !recForm) return;
                recForm.action = btn.dataset.action || '';
                document.getElementById('rec-modal-session').textContent = btn.dataset.session || '';
                document.getElementById('rec-modal-phone').textContent = btn.dataset.phone || '';
                document.getElementById('rec-modal-cost').value = btn.dataset.atCost || '7.50';
                document.getElementById('rec-modal-duration').value = btn.dataset.duration || '';
                const stSel = document.getElementById('rec-modal-status');
                const stVal = btn.dataset.status || 'Success';
                stSel.value = ['Success', 'Incomplete', 'Failed'].includes(stVal) ? stVal : 'Success';
                modal.classList.remove('hidden');
            });
        });
    }

    ['close-reconcile-modal', 'cancel-reconcile-modal'].forEach((id) => {
        const el = document.getElementById(id);
        el && el.addEventListener('click', () => modal && modal.classList.add('hidden'));
    });

    bindModalTriggers();

    async function fetchLiveLogs() {
        if (isFetching || !tableContainer || currentTab === 'reconcile') return;
        if (modal && !modal.classList.contains('hidden')) return;

        isFetching = true;
        refreshIcon && refreshIcon.classList.add('animate-spin');

        try {
            const url = @json(route('admin.logs.live')) + window.location.search;
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();

            tableContainer.innerHTML = data.html;
            bindModalTriggers();

            if (data.summary) {
                const map = {
                    'kpi-total-sessions': data.summary.total_sessions,
                    'kpi-total-upstream': data.summary.total_upstream,
                    'kpi-total-billed': data.summary.total_billed,
                    'kpi-total-margin': data.summary.total_margin,
                    'kpi-unreconciled': data.summary.unreconciled_count,
                    'kpi-zero-cost': data.summary.zero_cost_count,
                    'kpi-failed': data.summary.failed_count,
                    'kpi-webhook-failures': data.summary.webhook_failures,
                };
                Object.entries(map).forEach(([id, val]) => {
                    const el = document.getElementById(id);
                    if (el && val !== undefined) el.textContent = val;
                });
            }

            if (data.counts) {
                Object.entries(data.counts).forEach(([key, count]) => {
                    const el = document.getElementById('tab-count-' + key);
                    if (el) el.textContent = count;
                });
            }

            if (statusLabel && data.updated_at) {
                statusLabel.textContent = 'Updated ' + data.updated_at;
            }
            if (liveDot) {
                liveDot.classList.remove('bg-error');
                liveDot.classList.add('bg-[#10B981]');
            }
        } catch (err) {
            if (statusLabel) statusLabel.textContent = 'Sync retry pending';
            if (liveDot) {
                liveDot.classList.remove('bg-[#10B981]');
                liveDot.classList.add('bg-error');
            }
        } finally {
            isFetching = false;
            refreshIcon && refreshIcon.classList.remove('animate-spin');
        }
    }

    function scheduleRefresh() {
        if (timerId) {
            clearInterval(timerId);
            timerId = null;
        }
        if (!intervalSelect || currentTab === 'reconcile') return;
        const ms = parseInt(intervalSelect.value || '0', 10);
        if (ms > 0) {
            timerId = setInterval(() => {
                if (!document.hidden) fetchLiveLogs();
            }, ms);
        }
    }

    if (intervalSelect) {
        const saved = localStorage.getItem(storageKey);
        if (saved !== null) {
            intervalSelect.value = saved;
        }
        intervalSelect.addEventListener('change', () => {
            localStorage.setItem(storageKey, intervalSelect.value);
            scheduleRefresh();
        });
        scheduleRefresh();
    }

    refreshBtn && refreshBtn.addEventListener('click', fetchLiveLogs);

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && intervalSelect && parseInt(intervalSelect.value || '0', 10) > 0) {
            fetchLiveLogs();
        }
    });

    checkAtBalanceBtn && checkAtBalanceBtn.addEventListener('click', async () => {
        if (!atBalanceText) return;
        atBalanceText.textContent = 'Checking Africa\'s Talking wallet...';
        try {
            const res = await fetch(@json(route('admin.logs.at-balance')), {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            const data = await res.json();
            if (data.ok && data.balance) {
                atBalanceText.textContent = 'Live AT Wallet Balance: ' + data.balance;
            } else {
                atBalanceText.textContent = data.message || 'Unable to query AT wallet.';
            }
        } catch (e) {
            atBalanceText.textContent = 'AT wallet check failed.';
        }
    });
});
</script>
@endpush
