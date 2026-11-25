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
            @else
                <table class="w-full whitespace-nowrap text-sm">
                    <thead><tr class="bg-secondary/5"><th class="px-4 py-3 text-start">Client</th><th class="px-4 py-3 text-start">Phone</th><th class="px-4 py-3 text-start">Code</th><th class="px-4 py-3 text-start">Input Path</th><th class="px-4 py-3 text-start">Cost</th><th class="px-4 py-3 text-start">Time</th></tr></thead>
                    <tbody>
                        @forelse ($ussdLogs as $log)
                            <tr class="even:bg-secondary/5">
                                <td class="px-4 py-2.5 font-medium">{{ $log->client?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2.5">{{ $log->phone_number }}</td>
                                <td class="px-4 py-2.5">{{ $log->service_code }}</td>
                                <td class="px-4 py-2.5">{{ $log->session_data }}</td>
                                <td class="px-4 py-2.5">₦{{ number_format((float) $log->cost, 2) }}</td>
                                <td class="px-4 py-2.5 text-xs text-n100">{{ optional($log->created_at)->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-n100">No USSD logs.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
