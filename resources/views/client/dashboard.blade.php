@extends('layout.main')
@section('title', 'Client Dashboard — ' . config('app.name'))

@section('content')
<div class="main-inner">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 lg:mb-8">
        <div>
            <h2 class="h2">Welcome, {{ $client->name }}</h2>
            <p class="text-sm text-n100 mt-1">Assigned USSD Code: <span class="font-semibold text-primary">{{ $client->ussd_code ?: 'Unassigned' }}</span></p>
        </div>
        <div class="flex flex-wrap gap-3">
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
        <div class="box col-span-12 bg-n0 min-[650px]:col-span-6 3xl:col-span-3 border border-n30">
            <div class="bb-dashed mb-4 flex items-center justify-between pb-3">
                <span class="font-medium text-n200">Token Balance</span>
                <span class="flex size-9 items-center justify-center rounded-full bg-primary/10 text-primary"><i class="las la-wallet"></i></span>
            </div>
            <h4 class="h4 mb-2">₦{{ number_format((float) $client->token_balance, 2) }}</h4>
            <span class="text-xs text-primary">Active Status: {{ ucfirst($client->status) }}</span>
        </div>

        <div class="box col-span-12 bg-n0 min-[650px]:col-span-6 3xl:col-span-3 border border-n30">
            <div class="bb-dashed mb-4 flex items-center justify-between pb-3">
                <span class="font-medium text-n200">Total USSD Hits</span>
                <span class="flex size-9 items-center justify-center rounded-full bg-secondary/10 text-secondary"><i class="las la-phone-volume"></i></span>
            </div>
            <h4 class="h4 mb-2">{{ number_format($totalUssd) }}</h4>
            <span class="text-xs text-n100">Tier USSD Rate: ₦{{ number_format($ussdRate, 2) }}</span>
        </div>

        <div class="box col-span-12 bg-n0 min-[650px]:col-span-6 3xl:col-span-3 border border-n30">
            <div class="bb-dashed mb-4 flex items-center justify-between pb-3">
                <span class="font-medium text-n200">Total SMS Sent</span>
                <span class="flex size-9 items-center justify-center rounded-full bg-primary/10 text-primary"><i class="las la-sms"></i></span>
            </div>
            <h4 class="h4 mb-2">{{ number_format($totalSms) }}</h4>
            <span class="text-xs text-n100">SMS Rate: ₦{{ number_format($smsRate, 2) }} / page</span>
        </div>

        <div class="box col-span-12 bg-n0 min-[650px]:col-span-6 3xl:col-span-3 border border-n30">
            <div class="bb-dashed mb-4 flex items-center justify-between pb-3">
                <span class="font-medium text-n200">Airtime Vends</span>
                <span class="flex size-9 items-center justify-center rounded-full bg-warning/10 text-warning"><i class="las la-mobile-alt"></i></span>
            </div>
            <h4 class="h4 mb-2">{{ number_format($totalAirtime) }}</h4>
            <span class="text-xs text-n100">2FA: {{ $client->is_2fa_enabled ? 'Enabled' : 'Disabled' }}</span>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-4 xxl:gap-6">
        <!-- 7-Day Activity Chart -->
        <div class="box col-span-12 xl:col-span-8 bg-n0 border border-n30">
            <div class="bb-dashed mb-4 flex flex-wrap items-center justify-between gap-3 pb-4">
                <h4 class="h4">7-Day USSD &amp; SMS Traffic</h4>
                <div class="flex items-center gap-4 text-sm">
                    <span class="flex items-center gap-2"><span class="inline-block size-3 rounded-full bg-primary"></span> USSD</span>
                    <span class="flex items-center gap-2"><span class="inline-block size-3 rounded-full bg-secondary"></span> SMS</span>
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
                            <div class="w-4 rounded-t bg-primary transition-all" style="height: {{ $uVal > 0 ? $uPct : 3 }}%" title="USSD: {{ $uVal }}"></div>
                            <div class="w-4 rounded-t bg-secondary transition-all" style="height: {{ $sVal > 0 ? $sPct : 3 }}%" title="SMS: {{ $sVal }}"></div>
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
                    <div class="flex size-11 items-center justify-center rounded-full bg-[#FFC861] text-white"><i class="las la-sitemap"></i></div>
                    <span class="text-sm font-medium">USSD Builder</span>
                </a>
                <a href="{{ route('keys.index') }}" class="flex flex-col items-center gap-2 rounded-xl border border-n30 bg-primary/5 p-4 duration-300 hover:border-primary">
                    <div class="flex size-11 items-center justify-center rounded-full bg-[#8B5CF6] text-white"><i class="las la-key"></i></div>
                    <span class="text-sm font-medium">API Keys</span>
                </a>
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
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentUssdLogs as $log)
                            <tr class="even:bg-secondary/5">
                                <td class="px-4 py-2.5 font-medium">{{ $log->phone_number }}</td>
                                <td class="px-4 py-2.5">{{ \Illuminate\Support\Str::limit($log->session_data ?: 'Main Menu', 28) }}</td>
                                <td class="px-4 py-2.5">₦{{ number_format((float) $log->cost, 2) }}</td>
                                <td class="px-4 py-2.5 text-xs text-n100">{{ optional($log->created_at)->format('M d, H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-n100">No USSD sessions logged yet.</td>
                            </tr>
                        @endforelse
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
@endsection
