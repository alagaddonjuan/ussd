@extends('layout.main')
@section('title', 'Billing & Wallet — ' . config('app.name'))

@section('content')
<div class="main-inner">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 lg:mb-8">
        <div>
            <h2 class="h2">Billing &amp; Wallet</h2>
            <p class="text-sm text-n100 mt-1">Current Wallet Balance: <span class="font-semibold text-primary">₦{{ number_format((float) $client->token_balance, 2) }}</span></p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('settings.profile') }}" class="btn-outline py-2 px-4 text-xs">
                <i class="las la-bell"></i> Low Balance Alert: ₦{{ number_format((float) ($client->low_balance_threshold ?? 5000), 2) }}
            </a>
            <a href="{{ route('export.ussd') }}" class="btn-secondary py-2 px-4 text-xs">
                <i class="las la-file-csv"></i> Statement CSV
            </a>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-4 mb-6">
        <div class="box col-span-12 md:col-span-6 border border-n30 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-n100">Monthly Channel Maintenance</p>
                <h5 class="h5 mt-1">₦{{ number_format((float) ($client->monthly_maintenance_fee ?? 159500), 2) }} / month</h5>
                <p class="text-xs text-n100 mt-1">
                    Last Billed: {{ $client->last_maintenance_billed_at ? $client->last_maintenance_billed_at->format('M d, Y') : 'Pending 1st of month cycle' }}
                </p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $client->maintenance_enabled ? 'bg-[#10B981]/15 text-[#10B981]' : 'bg-n30/40 text-n100' }}">
                {{ $client->maintenance_enabled ? 'Active' : 'Waived' }}
            </span>
        </div>

        <div class="box col-span-12 md:col-span-6 border border-n30 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-n100">KYC Verification Status</p>
                <h5 class="h5 mt-1">{{ ucfirst($client->kyc_status ?? 'unverified') }}</h5>
                <p class="text-xs text-n100 mt-1">Send CAC / Request Letter / Valid ID to support@q4iltd.com</p>
            </div>
            <a href="{{ route('settings.profile') }}" class="btn-outline py-1.5 px-4 text-xs">Manage Alerts</a>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6">
        <div class="box col-span-12 lg:col-span-4 border border-n30">
            <h4 class="h4 bb-dashed pb-4 mb-4">Fund Wallet via Paystack</h4>
            @if (! $paystackReady)
                <div class="mb-4 rounded-xl bg-warning/10 p-3 text-xs text-warning">
                    Paystack keys are not set in <code>.env</code> yet (<code>PAYSTACK_SECRET_KEY</code>). Add them to enable live checkout.
                </div>
            @endif
            <form method="POST" action="{{ route('billing.paystack') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="amount" class="block font-medium mb-2">Amount (NGN)</label>
                    <input type="number" name="amount" id="amount" min="100" step="100" value="{{ old('amount', 5000) }}" required
                        class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-3 text-sm" />
                </div>
                <button type="submit" class="btn-primary w-full justify-center">
                    <i class="las la-credit-card"></i>
                    <span>Pay with Paystack</span>
                </button>
            </form>
        </div>

        <div class="box col-span-12 lg:col-span-8 border border-n30">
            <h4 class="h4 bb-dashed pb-4 mb-4">Transaction History</h4>
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-sm">
                    <thead>
                        <tr class="bg-secondary/5">
                            <th class="px-4 py-3 text-start">Reference</th>
                            <th class="px-4 py-3 text-start">Type</th>
                            <th class="px-4 py-3 text-start">Gateway</th>
                            <th class="px-4 py-3 text-start">Amount</th>
                            <th class="px-4 py-3 text-start">Status</th>
                            <th class="px-4 py-3 text-start">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $tx)
                            <tr class="even:bg-secondary/5">
                                <td class="px-4 py-2.5 font-medium">{{ $tx->reference }}</td>
                                <td class="px-4 py-2.5 uppercase text-xs">{{ $tx->type }}</td>
                                <td class="px-4 py-2.5 uppercase text-xs">{{ $tx->gateway }}</td>
                                <td class="px-4 py-2.5">₦{{ number_format((float) $tx->amount, 2) }}</td>
                                <td class="px-4 py-2.5">
                                    @php $ok = strtolower((string) $tx->status) === 'success'; @endphp
                                    <span class="rounded-full px-3 py-1 text-xs {{ $ok ? 'bg-primary/10 text-primary' : 'bg-warning/10 text-warning' }}">
                                        {{ ucfirst($tx->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-xs text-n100">{{ optional($tx->created_at)->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-n100">No transactions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
