@extends('layout.main')
@section('title', 'System Logs — ' . config('app.name'))

@section('content')
<div class="main-inner">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 lg:mb-8">
        <h2 class="h2">Platform System Logs</h2>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.reports.export', 'ussd') }}" class="btn-outline py-2 px-4 text-xs"><i class="las la-download"></i> USSD CSV</a>
            <a href="{{ route('admin.reports.export', 'sms') }}" class="btn-outline py-2 px-4 text-xs"><i class="las la-download"></i> SMS CSV</a>
            <a href="{{ route('admin.reports.export', 'airtime') }}" class="btn-outline py-2 px-4 text-xs"><i class="las la-download"></i> Airtime CSV</a>
        </div>
    </div>

    <div class="box border border-n30">
        <div class="tab-navigation mb-6 overflow-x-auto">
            <a href="{{ route('admin.logs', ['tab' => 'ussd']) }}" class="tab-link {{ $tab === 'ussd' ? 'active' : '' }}">USSD Logs ({{ $ussdLogs->count() }})</a>
            <a href="{{ route('admin.logs', ['tab' => 'sms']) }}" class="tab-link {{ $tab === 'sms' ? 'active' : '' }}">SMS Logs ({{ $smsLogs->count() }})</a>
            <a href="{{ route('admin.logs', ['tab' => 'airtime']) }}" class="tab-link {{ $tab === 'airtime' ? 'active' : '' }}">Airtime Logs ({{ $airtimeLogs->count() }})</a>
            <a href="{{ route('admin.logs', ['tab' => 'transactions']) }}" class="tab-link {{ $tab === 'transactions' ? 'active' : '' }}">Transactions ({{ $transactions->count() }})</a>
            <a href="{{ route('admin.logs', ['tab' => 'webhooks']) }}" class="tab-link {{ $tab === 'webhooks' ? 'active' : '' }}">Webhooks ({{ $webhookLogs->count() }})</a>
            <a href="{{ route('admin.logs', ['tab' => 'audit']) }}" class="tab-link {{ $tab === 'audit' ? 'active' : '' }}">Audit Trail ({{ $auditLogs->count() }})</a>
        </div>

        <div class="overflow-x-auto">
            @if ($tab === 'sms')
                <table class="w-full whitespace-nowrap text-sm">
                    <thead><tr class="bg-secondary/5"><th class="px-4 py-3 text-start">Client</th><th class="px-4 py-3 text-start">Phone</th><th class="px-4 py-3 text-start">Message</th><th class="px-4 py-3 text-start">Cost</th><th class="px-4 py-3 text-start">Status</th><th class="px-4 py-3 text-start">Time</th></tr></thead>
                    <tbody>
                        @forelse ($smsLogs as $log)
                            <tr class="even:bg-secondary/5">
                                <td class="px-4 py-2.5 font-medium">{{ $log->client?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2.5">{{ $log->phone_number }}</td>
                                <td class="px-4 py-2.5">{{ \Illuminate\Support\Str::limit($log->message, 40) }}</td>
                                <td class="px-4 py-2.5">₦{{ number_format((float) $log->cost, 2) }}</td>
                                <td class="px-4 py-2.5">{{ $log->status }}</td>
                                <td class="px-4 py-2.5 text-xs text-n100">{{ optional($log->created_at)->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-n100">No SMS logs.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @elseif ($tab === 'airtime')
                <table class="w-full whitespace-nowrap text-sm">
                    <thead><tr class="bg-secondary/5"><th class="px-4 py-3 text-start">Client</th><th class="px-4 py-3 text-start">Phone</th><th class="px-4 py-3 text-start">Network</th><th class="px-4 py-3 text-start">Amount</th><th class="px-4 py-3 text-start">Status</th><th class="px-4 py-3 text-start">Time</th></tr></thead>
                    <tbody>
                        @forelse ($airtimeLogs as $log)
                            <tr class="even:bg-secondary/5">
                                <td class="px-4 py-2.5 font-medium">{{ $log->client?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2.5">{{ $log->phone_number }}</td>
                                <td class="px-4 py-2.5">{{ $log->network }}</td>
                                <td class="px-4 py-2.5">₦{{ number_format((float) $log->amount, 2) }}</td>
                                <td class="px-4 py-2.5">{{ $log->status }}</td>
                                <td class="px-4 py-2.5 text-xs text-n100">{{ optional($log->created_at)->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-n100">No airtime logs.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @elseif ($tab === 'transactions')
                <table class="w-full whitespace-nowrap text-sm">
                    <thead><tr class="bg-secondary/5"><th class="px-4 py-3 text-start">Client</th><th class="px-4 py-3 text-start">Reference</th><th class="px-4 py-3 text-start">Type</th><th class="px-4 py-3 text-start">Gateway</th><th class="px-4 py-3 text-start">Amount</th><th class="px-4 py-3 text-start">Status</th><th class="px-4 py-3 text-start">Time</th></tr></thead>
                    <tbody>
                        @forelse ($transactions as $tx)
                            <tr class="even:bg-secondary/5">
                                <td class="px-4 py-2.5 font-medium">{{ $tx->client?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2.5 text-xs">{{ $tx->reference }}</td>
                                <td class="px-4 py-2.5 uppercase text-xs">{{ $tx->type }}</td>
                                <td class="px-4 py-2.5 uppercase text-xs">{{ $tx->gateway }}</td>
                                <td class="px-4 py-2.5">₦{{ number_format((float) $tx->amount, 2) }}</td>
                                <td class="px-4 py-2.5">{{ ucfirst($tx->status) }}</td>
                                <td class="px-4 py-2.5 text-xs text-n100">{{ optional($tx->created_at)->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-6 text-center text-n100">No transactions.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @elseif ($tab === 'webhooks')
                <table class="w-full whitespace-nowrap text-sm">
                    <thead><tr class="bg-secondary/5"><th class="px-4 py-3 text-start">Provider</th><th class="px-4 py-3 text-start">Endpoint</th><th class="px-4 py-3 text-start">Status</th><th class="px-4 py-3 text-start">Latency</th><th class="px-4 py-3 text-start">Outcome</th><th class="px-4 py-3 text-start">Time</th></tr></thead>
                    <tbody>
                        @forelse ($webhookLogs as $wh)
                            <tr class="even:bg-secondary/5">
                                <td class="px-4 py-2.5 font-medium uppercase text-xs">{{ $wh->provider }}</td>
                                <td class="px-4 py-2.5 text-xs">{{ $wh->endpoint }}</td>
                                <td class="px-4 py-2.5">{{ $wh->status_code ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-xs">{{ $wh->latency_ms ? $wh->latency_ms . ' ms' : '—' }}</td>
                                <td class="px-4 py-2.5 text-xs">{{ $wh->outcome }}</td>
                                <td class="px-4 py-2.5 text-xs text-n100">{{ optional($wh->created_at)->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-n100">No webhook deliveries logged yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @elseif ($tab === 'audit')
                <table class="w-full whitespace-nowrap text-sm">
                    <thead><tr class="bg-secondary/5"><th class="px-4 py-3 text-start">Actor</th><th class="px-4 py-3 text-start">Action</th><th class="px-4 py-3 text-start">Details</th><th class="px-4 py-3 text-start">IP</th><th class="px-4 py-3 text-start">Time</th></tr></thead>
                    <tbody>
                        @forelse ($auditLogs as $audit)
                            <tr class="even:bg-secondary/5">
                                <td class="px-4 py-2.5 font-medium">{{ $audit->actor?->name ?? 'System' }}</td>
                                <td class="px-4 py-2.5 text-xs font-semibold text-primary">{{ $audit->action }}</td>
                                <td class="px-4 py-2.5 text-xs">{{ \Illuminate\Support\Str::limit($audit->details, 70) }}</td>
                                <td class="px-4 py-2.5 text-xs">{{ $audit->ip_address ?: '—' }}</td>
                                <td class="px-4 py-2.5 text-xs text-n100">{{ optional($audit->created_at)->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-n100">No admin audit actions recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table class="w-full whitespace-nowrap text-sm">
                    <thead><tr class="bg-secondary/5"><th class="px-4 py-3 text-start">Client</th><th class="px-4 py-3 text-start">Phone</th><th class="px-4 py-3 text-start">Carrier</th><th class="px-4 py-3 text-start">Hops / Dur.</th><th class="px-4 py-3 text-start">Billed</th><th class="px-4 py-3 text-start">Upstream</th><th class="px-4 py-3 text-start">Margin</th><th class="px-4 py-3 text-start">Status</th><th class="px-4 py-3 text-start">Time</th></tr></thead>
                    <tbody>
                        @forelse ($ussdLogs as $log)
                            @php
                                $billed = (float) ($log->client_price ?? $log->cost ?? 0);
                                $up = (float) ($log->upstream_cost ?? 0);
                                $margin = round($billed - $up, 2);
                            @endphp
                            <tr class="even:bg-secondary/5">
                                <td class="px-4 py-2.5 font-medium">{{ $log->client?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2.5">{{ $log->phone_number }}</td>
                                <td class="px-4 py-2.5 text-xs">{{ $log->network_name ?: '—' }}</td>
                                <td class="px-4 py-2.5 text-xs">{{ $log->hops ?? 1 }} / {{ $log->duration_ms ? round($log->duration_ms / 1000) . 's' : '—' }}</td>
                                <td class="px-4 py-2.5 font-semibold">₦{{ number_format($billed, 2) }}</td>
                                <td class="px-4 py-2.5 text-xs">₦{{ number_format($up, 2) }}</td>
                                <td class="px-4 py-2.5 text-xs font-semibold text-[#10B981]">₦{{ number_format($margin, 2) }}</td>
                                <td class="px-4 py-2.5 text-xs">{{ $log->status }}</td>
                                <td class="px-4 py-2.5 text-xs text-n100">{{ optional($log->created_at)->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-6 text-center text-n100">No USSD logs.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
