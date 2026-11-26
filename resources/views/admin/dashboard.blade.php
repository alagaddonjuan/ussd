@extends('layout.main')
@section('title', 'Admin Overview — ' . config('app.name'))

@section('content')
<div class="main-inner">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 lg:mb-8">
        <div>
            <h2 class="h2">Admin Overview</h2>
            <p class="text-sm text-n100 mt-1">Platform-wide traffic, revenue, and client telemetry</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.clients') }}" class="btn-outline"><i class="las la-users"></i> Manage Clients</a>
            <a href="{{ route('admin.pricing-tiers') }}" class="btn-primary"><i class="las la-tags"></i> Pricing &amp; Telcos</a>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-4 xxl:gap-6 mb-6">
        <div class="box col-span-12 bg-n0 min-[650px]:col-span-6 3xl:col-span-3 border border-n30 border-t-2 border-t-[#0878F8]">
            <div class="bb-dashed mb-4 flex items-center justify-between pb-3">
                <span class="font-medium text-n200">Registered Clients</span>
                <span class="flex size-9 items-center justify-center rounded-full bg-[#0878F8]/12 text-[#0878F8]"><i class="las la-users"></i></span>
            </div>
            <h4 class="h4">{{ number_format($totalClients) }}</h4>
            <p class="text-xs text-n100 mt-1">Pending KYC: {{ $pendingKycCount }}</p>
        </div>

        <div class="box col-span-12 bg-n0 min-[650px]:col-span-6 3xl:col-span-3 border border-n30 border-t-2 border-t-[#0B3A75]">
            <div class="bb-dashed mb-4 flex items-center justify-between pb-3">
                <span class="font-medium text-n200">USSD Billed / Upstream</span>
                <span class="flex size-9 items-center justify-center rounded-full bg-[#0B3A75]/12 text-[#0B3A75] dark:text-[#22C7F8]"><i class="las la-chart-line"></i></span>
            </div>
            <h4 class="h4">₦{{ number_format($ussdClientRevenue, 2) }}</h4>
            <p class="text-xs text-n100 mt-1">Upstream: ₦{{ number_format($ussdUpstreamCost, 2) }} &bull; Hits: {{ number_format($totalUssd) }}</p>
        </div>

        <div class="box col-span-12 bg-n0 min-[650px]:col-span-6 3xl:col-span-3 border border-n30 border-t-2 border-t-[#10B981]">
            <div class="bb-dashed mb-4 flex items-center justify-between pb-3">
                <span class="font-medium text-n200">Net USSD Margin</span>
                <span class="flex size-9 items-center justify-center rounded-full bg-[#10B981]/15 text-[#10B981]"><i class="las la-percentage"></i></span>
            </div>
            <h4 class="h4 text-[#10B981]">₦{{ number_format($ussdNetMargin, 2) }}</h4>
            <p class="text-xs text-n100 mt-1">SMS Sent: {{ number_format($totalSms) }} &bull; Webhook Alerts (24h): {{ $recentWebhookErrors }}</p>
        </div>

        <div class="box col-span-12 bg-n0 min-[650px]:col-span-6 3xl:col-span-3 border border-n30 border-t-2 border-t-[#6D5EF7]">
            <div class="bb-dashed mb-4 flex items-center justify-between pb-3">
                <span class="font-medium text-n200">Wallet Revenue / Maint. MRR</span>
                <span class="flex size-9 items-center justify-center rounded-full bg-[#6D5EF7]/15 text-[#6D5EF7]"><i class="las la-coins"></i></span>
            </div>
            <h4 class="h4">₦{{ number_format($totalRevenue, 2) }}</h4>
            <p class="text-xs text-n100 mt-1">Monthly Maint. MRR: ₦{{ number_format($activeMaintenanceMrr, 2) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6">
        <div class="box col-span-12 xl:col-span-6 border border-n30">
            <div class="bb-dashed mb-4 flex items-center justify-between pb-4">
                <h4 class="h4">7-Day Platform Traffic</h4>
                <div class="flex items-center gap-4 text-sm">
                    <span class="flex items-center gap-2"><span class="inline-block size-3 rounded-full bg-[#0878F8]"></span> USSD</span>
                    <span class="flex items-center gap-2"><span class="inline-block size-3 rounded-full bg-[#22C7F8]"></span> SMS</span>
                </div>
            </div>
            @php $maxVal = max(1, ...$ussdSeries, ...$smsSeries); @endphp
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
                            <div class="w-4 rounded-t bg-[#0878F8]" style="height: {{ $uVal > 0 ? $uPct : 3 }}%" title="USSD: {{ $uVal }}"></div>
                            <div class="w-4 rounded-t bg-[#22C7F8]" style="height: {{ $sVal > 0 ? $sPct : 3 }}%" title="SMS: {{ $sVal }}"></div>
                        </div>
                        <div class="text-center">
                            <p class="text-xs font-medium">{{ $label }}</p>
                            <p class="text-[11px] text-n100">{{ $uVal }} / {{ $sVal }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="box col-span-12 xl:col-span-6 border border-n30">
            <div class="bb-dashed mb-4 flex items-center justify-between pb-4">
                <h4 class="h4">Latest Transactions</h4>
                <a href="{{ route('admin.logs', ['tab' => 'transactions']) }}" class="text-sm text-primary font-medium">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-sm">
                    <thead>
                        <tr class="bg-secondary/5">
                            <th class="px-4 py-3 text-start">Client</th>
                            <th class="px-4 py-3 text-start">Gateway</th>
                            <th class="px-4 py-3 text-start">Amount</th>
                            <th class="px-4 py-3 text-start">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentTransactions as $tx)
                            <tr class="even:bg-secondary/5">
                                <td class="px-4 py-2.5 font-medium">{{ $tx->client?->name ?? 'N/A' }}</td>
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
                            <tr><td colspan="4" class="px-4 py-6 text-center text-n100">No transactions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
