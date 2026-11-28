@extends('layout.main')
@section('title', 'Client Dashboard — ' . config('app.name'))

@section('content')
<div class="main-inner">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 lg:mb-8">
        <div>
            <h2 class="h2">Welcome, {{ $client->name }}</h2>
            <p class="text-sm text-n100 mt-1">Assigned USSD Code: <span class="font-semibold text-primary">{{ $client->ussd_code ?: 'Unassigned' }}</span></p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2 rounded-3xl border border-n30 bg-n0 px-3 py-1.5">
                <span class="text-xs text-n100" id="dash-live-updated">Live {{ now()->format('H:i:s') }}</span>
                <select id="dash-auto-refresh" aria-label="Dashboard auto refresh"
                    class="rounded-2xl border border-n30 bg-primary/5 px-2 py-1 text-xs font-medium">
                    <option value="0">Auto: Off</option>
                    <option value="5000">Auto: 5s</option>
                    <option value="10000" selected>Auto: 10s</option>
                    <option value="30000">Auto: 30s</option>
                </select>
                <button type="button" id="dash-refresh-btn" class="btn-outline py-1 px-2.5 text-xs">
                    <i class="las la-sync" id="dash-refresh-icon"></i>
                    <span>Refresh</span>
                </button>
            </div>
            <a href="{{ route('ussd.menus.index') }}" class="btn-outline">
                <i class="las la-sitemap"></i>
                <span>Menu Builder</span>
            </a>
            <a href="{{ route('billing.index') }}" class="btn-primary">
                <i class="las la-plus-circle"></i>
                <span>Fund Wallet</span>
            </a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-12 gap-4 xxl:gap-6 mb-6">
        <div class="box col-span-12 bg-n0 min-[650px]:col-span-6 3xl:col-span-3 border border-n30 border-t-2 border-t-[#0878F8]">
            <div class="bb-dashed mb-4 flex items-center justify-between pb-3">
                <span class="font-medium text-n200">Token Balance</span>
                <span class="flex size-9 items-center justify-center rounded-full bg-[#0878F8]/12 text-[#0878F8]"><i class="las la-wallet"></i></span>
            </div>
            <h4 class="h4 mb-2" id="dash-token-balance">₦{{ number_format((float) $client->token_balance, 2) }}</h4>
            <span class="text-xs font-semibold {{ $client->status === 'active' ? 'text-[#10B981]' : 'text-error' }}">
                Status: {{ ucfirst($client->status) }} &bull; Alert @ ₦{{ number_format((float) ($client->low_balance_threshold ?? 5000), 0) }}
            </span>
        </div>

        <div class="box col-span-12 bg-n0 min-[650px]:col-span-6 3xl:col-span-3 border border-n30 border-t-2 border-t-[#0B3A75]">
            <div class="bb-dashed mb-4 flex items-center justify-between pb-3">
                <span class="font-medium text-n200">Total USSD Sessions</span>
                <span class="flex size-9 items-center justify-center rounded-full bg-[#0B3A75]/12 text-[#0B3A75] dark:text-[#22C7F8]"><i class="las la-phone-volume"></i></span>
            </div>
            <h4 class="h4 mb-2" id="dash-total-ussd">{{ number_format($totalUssd) }}</h4>
            <span class="text-xs text-n100">
                {{ $client->pricingTier?->tier_name ?? 'Basic Plan' }} &bull;
                MTN ₦{{ number_format($telcoRates['mtn']['rate'], 2) }}/20s &bull;
                Airtel ₦{{ number_format($telcoRates['airtel']['rate'], 2) }}/20s &bull;
                Glo ₦{{ number_format($telcoRates['glo']['rate'], 2) }}/120s &bull;
                T2 ₦{{ number_format($telcoRates['t2']['rate'], 2) }}/20s
            </span>
        </div>

        <div class="box col-span-12 bg-n0 min-[650px]:col-span-6 3xl:col-span-3 border border-n30 border-t-2 border-t-[#22C7F8]">
            <div class="bb-dashed mb-4 flex items-center justify-between pb-3">
                <span class="font-medium text-n200">Total SMS Sent</span>
                <span class="flex size-9 items-center justify-center rounded-full bg-[#22C7F8]/15 text-[#0878F8]"><i class="las la-sms"></i></span>
            </div>
            <h4 class="h4 mb-2">{{ number_format($totalSms) }}</h4>
            <span class="text-xs text-n100">SMS Rate: ₦{{ number_format($smsRate, 2) }} / page</span>
        </div>

        <div class="box col-span-12 bg-n0 min-[650px]:col-span-6 3xl:col-span-3 border border-n30 border-t-2 border-t-[#6D5EF7]">
            <div class="bb-dashed mb-4 flex items-center justify-between pb-3">
                <span class="font-medium text-n200">Airtime Vends</span>
                <span class="flex size-9 items-center justify-center rounded-full bg-[#6D5EF7]/15 text-[#6D5EF7]"><i class="las la-mobile-alt"></i></span>
            </div>
            <h4 class="h4 mb-2">{{ number_format($totalAirtime) }}</h4>
            <span class="text-xs text-n100">
                Maint: ₦{{ number_format((float) ($client->monthly_maintenance_fee ?? 159500), 0) }}/mo &bull; 2FA: {{ $client->is_2fa_enabled ? 'On' : 'Off' }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-4 xxl:gap-6">
        <!-- 7-Day Activity Chart -->
        <div class="box col-span-12 xl:col-span-8 bg-n0 border border-n30">
            <div class="bb-dashed mb-4 flex flex-wrap items-center justify-between gap-3 pb-4">
                <h4 class="h4">7-Day USSD &amp; SMS Traffic</h4>
                <div class="flex items-center gap-4 text-sm">
                    <span class="flex items-center gap-2"><span class="inline-block size-3 rounded-full bg-[#0878F8]"></span> USSD</span>
                    <span class="flex items-center gap-2"><span class="inline-block size-3 rounded-full bg-[#22C7F8]"></span> SMS</span>
                </div>
            </div>
            @php
                $maxVal = max(1, ...$ussdSeries, ...$smsSeries);
            @endphp
            <div class="grid grid-cols-7 gap-3 items-end h-56 pt-6 pb-2">
                @foreach ($labels as $idx => $label)
                    @php
                        $uVal = $ussdSeries[$idx] ?? 0;
                        $sVal = $smsSeries[$idx] ?? 0;
                        $uPct = max(4, round(($uVal / $maxVal) * 100));
                        $sPct = max(4, round(($sVal / $maxVal) * 100));
                    @endphp
                    <div class="flex flex-col items-center h-full justify-end gap-2">
                        <div class="flex items-end gap-1.5 w-full justify-center h-40">
                            <div class="w-4 rounded-t bg-[#0878F8] transition-all" style="height: {{ $uVal > 0 ? $uPct : 3 }}%" title="USSD: {{ $uVal }}"></div>
                            <div class="w-4 rounded-t bg-[#22C7F8] transition-all" style="height: {{ $sVal > 0 ? $sPct : 3 }}%" title="SMS: {{ $sVal }}"></div>
                        </div>
                        <div class="text-center">
                            <p class="text-xs font-medium">{{ $label }}</p>
                            <p class="text-[11px] text-n100">{{ $uVal }} / {{ $sVal }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="box col-span-12 xl:col-span-4 bg-n0 border border-n30">
            <div class="bb-dashed mb-4 pb-4">
                <h4 class="h4">Quick Actions</h4>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('services.index') }}" class="flex flex-col items-center gap-2 rounded-xl border border-n30 bg-primary/5 p-4 duration-300 hover:border-primary">
                    <div class="flex size-11 items-center justify-center rounded-full bg-primary text-white"><i class="las la-paper-plane"></i></div>
                    <span class="text-sm font-medium">Send SMS</span>
                </a>
                <a href="{{ route('services.index') }}#airtime" class="flex flex-col items-center gap-2 rounded-xl border border-n30 bg-primary/5 p-4 duration-300 hover:border-primary">
                    <div class="flex size-11 items-center justify-center rounded-full bg-secondary text-white"><i class="las la-mobile"></i></div>
                    <span class="text-sm font-medium">Send Airtime</span>
                </a>
                <a href="{{ route('ussd.menus.index') }}" class="flex flex-col items-center gap-2 rounded-xl border border-n30 bg-primary/5 p-4 duration-300 hover:border-primary">
                    <div class="flex size-11 items-center justify-center rounded-full bg-[#10B981] text-white"><i class="las la-sitemap"></i></div>
                    <span class="text-sm font-medium">USSD Builder</span>
                </a>
                <a href="{{ route('keys.index') }}" class="flex flex-col items-center gap-2 rounded-xl border border-n30 bg-primary/5 p-4 duration-300 hover:border-primary">
                    <div class="flex size-11 items-center justify-center rounded-full bg-[#6D5EF7] text-white"><i class="las la-key"></i></div>
                    <span class="text-sm font-medium">API Keys</span>
                </a>
            </div>
        </div>

        <!-- Session Telemetry & Drop-Off Funnel -->
        <div class="box col-span-12 bg-n0 border border-n30">
            <div class="bb-dashed mb-4 flex flex-wrap items-center justify-between gap-3 pb-4">
                <div>
                    <h4 class="h4">USSD Session Telemetry &amp; Carrier Mix</h4>
                    <p class="text-xs text-n100 mt-0.5">Completion health, average duration, hop depth, and Nigerian telco distribution</p>
                </div>
                <span class="rounded-full bg-[#10B981]/15 px-3 py-1 text-xs font-bold text-[#10B981]">
                    <span id="dash-completion-rate">{{ $completionRate }}%</span> Completion Rate
                </span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl border border-n30 bg-primary/5 p-4">
                    <p class="text-xs text-n100">Completed vs Incomplete</p>
                    <p class="text-xl font-bold text-[#0B3A75] dark:text-white mt-1">
                        <span id="dash-completed-sessions">{{ number_format($completedSessions) }}</span>
                        <span class="text-xs font-normal text-error">/ <span id="dash-incomplete-sessions">{{ number_format($incompleteSessions) }}</span> dropped</span>
                    </p>
                </div>
                <div class="rounded-2xl border border-n30 bg-primary/5 p-4">
                    <p class="text-xs text-n100">Avg. Session Duration</p>
                    <p class="text-xl font-bold text-[#0878F8] mt-1" id="dash-avg-duration">{{ $avgDurationSec }}s</p>
                </div>
                <div class="rounded-2xl border border-n30 bg-primary/5 p-4">
                    <p class="text-xs text-n100">Avg. Menu Depth</p>
                    <p class="text-xl font-bold text-[#6D5EF7] mt-1" id="dash-avg-hops">{{ $avgHops }} hops</p>
                </div>
                <div class="rounded-2xl border border-n30 bg-primary/5 p-4">
                    <p class="text-xs text-n100 mb-1.5">Carrier Breakdown</p>
                    @if (! empty($carrierBreakdown))
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($carrierBreakdown as $carrier => $hits)
                                <span class="rounded-full bg-white/80 dark:bg-white/10 border border-n30 px-2.5 py-0.5 text-[11px] font-semibold">
                                    {{ $carrier }}: {{ $hits }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-n100">Awaiting carrier traffic</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent USSD Sessions -->
        <div class="box col-span-12 lg:col-span-6 bg-n0 border border-n30">
            <div class="bb-dashed mb-4 flex items-center justify-between pb-4">
                <h4 class="h4">Recent USSD Sessions</h4>
                <a href="{{ route('export.ussd') }}" class="text-sm text-primary font-medium">Export CSV</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-sm">
                    <thead>
                        <tr class="bg-secondary/5">
                            <th class="px-4 py-3 text-start">Phone</th>
                            <th class="px-4 py-3 text-start">Input Path</th>
                            <th class="px-4 py-3 text-start">Cost</th>
                            <th class="px-4 py-3 text-start">Time</th>
                            <th class="px-4 py-3 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="dash-recent-ussd-tbody">
                        @include('client.partials._dashboard-ussd-rows')
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="box col-span-12 lg:col-span-6 bg-n0 border border-n30">
            <div class="bb-dashed mb-4 flex items-center justify-between pb-4">
                <h4 class="h4">Recent Wallet Transactions</h4>
                <a href="{{ route('billing.index') }}" class="text-sm text-primary font-medium">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-sm">
                    <thead>
                        <tr class="bg-secondary/5">
                            <th class="px-4 py-3 text-start">Reference</th>
                            <th class="px-4 py-3 text-start">Gateway</th>
                            <th class="px-4 py-3 text-start">Amount</th>
                            <th class="px-4 py-3 text-start">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentTransactions as $tx)
                            <tr class="even:bg-secondary/5">
                                <td class="px-4 py-2.5 font-medium">{{ \Illuminate\Support\Str::limit($tx->reference, 22) }}</td>
                                <td class="px-4 py-2.5 uppercase text-xs">{{ $tx->gateway }}</td>
                                <td class="px-4 py-2.5">₦{{ number_format((float) $tx->amount, 2) }}</td>
                                <td class="px-4 py-2.5">
                                    @php $ok = strtolower((string)$tx->status) === 'success'; @endphp
                                    <span class="rounded-full px-3 py-1 text-xs {{ $ok ? 'bg-primary/10 text-primary' : 'bg-warning/10 text-warning' }}">
                                        {{ ucfirst($tx->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-n100">No wallet transactions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@include('partials._ussd-session-details-modal')
@endsection

@push('page-js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const refreshBtn = document.getElementById('dash-refresh-btn');
    const refreshIcon = document.getElementById('dash-refresh-icon');
    const intervalSelect = document.getElementById('dash-auto-refresh');
    const updatedLabel = document.getElementById('dash-live-updated');
    const tbody = document.getElementById('dash-recent-ussd-tbody');
    const storageKey = 'q4_client_dash_refresh_ms';
    let timerId = null;
    let busy = false;

    async function refreshDashboard() {
        if (busy || !tbody) return;
        busy = true;
        refreshIcon && refreshIcon.classList.add('animate-spin');
        try {
            const res = await fetch(@json(route('dashboard.live')), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            tbody.innerHTML = data.recent_ussd_html;

            const fields = {
                'dash-token-balance': data.token_balance,
                'dash-total-ussd': data.total_ussd,
                'dash-completion-rate': data.completion_rate,
                'dash-completed-sessions': data.completed_sessions,
                'dash-incomplete-sessions': data.incomplete_sessions,
                'dash-avg-duration': data.avg_duration_sec,
                'dash-avg-hops': data.avg_hops,
            };
            Object.entries(fields).forEach(([id, val]) => {
                const el = document.getElementById(id);
                if (el && val !== undefined) el.textContent = val;
            });

            if (updatedLabel && data.updated_at) updatedLabel.textContent = 'Live ' + data.updated_at;
        } catch (e) {
            if (updatedLabel) updatedLabel.textContent = 'Sync retry pending';
        } finally {
            busy = false;
            refreshIcon && refreshIcon.classList.remove('animate-spin');
        }
    }

    function schedule() {
        if (timerId) clearInterval(timerId);
        const ms = parseInt(intervalSelect?.value || '0', 10);
        if (ms > 0) {
            timerId = setInterval(() => {
                if (!document.hidden) refreshDashboard();
            }, ms);
        }
    }

    if (intervalSelect) {
        const saved = localStorage.getItem(storageKey);
        if (saved !== null) intervalSelect.value = saved;
        intervalSelect.addEventListener('change', () => {
            localStorage.setItem(storageKey, intervalSelect.value);
            schedule();
        });
        schedule();
    }

    refreshBtn && refreshBtn.addEventListener('click', refreshDashboard);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && parseInt(intervalSelect?.value || '0', 10) > 0) refreshDashboard();
    });
});
</script>
@endpush
