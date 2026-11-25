@extends('layout.main')
@section('title', 'Pricing Tiers & Telco USSD Rates — ' . config('app.name'))

@section('content')
<div class="main-inner">
    <div class="mb-6 lg:mb-8">
        <h2 class="h2">Pricing Tiers &amp; Telco USSD Rates</h2>
    </div>

    <div class="grid grid-cols-12 gap-6 mb-6">
        <!-- Create Pricing Tier -->
        <div class="box col-span-12 lg:col-span-4 border border-n30">
            <h4 class="h4 bb-dashed pb-4 mb-4">Create Pricing Tier</h4>
            <form method="POST" action="{{ route('admin.pricing-tiers.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1">Tier Name</label>
                    <input type="text" name="tier_name" required class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-2.5 text-sm" placeholder="Enterprise Plan" />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Description</label>
                    <input type="text" name="description" class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-2.5 text-sm" placeholder="Optional notes" />
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-xs font-medium mb-1">SMS</label>
                        <input type="number" step="0.01" min="0" name="sms_price" value="2.50" required class="w-full rounded-2xl border border-n30 bg-primary/5 px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">USSD</label>
                        <input type="number" step="0.01" min="0" name="ussd_price" value="3.50" required class="w-full rounded-2xl border border-n30 bg-primary/5 px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Airtime</label>
                        <input type="number" step="0.01" min="0" name="airtime_price" value="1.00" required class="w-full rounded-2xl border border-n30 bg-primary/5 px-3 py-2 text-sm" />
                    </div>
                </div>
                <button type="submit" class="btn-primary w-full justify-center">Create Tier</button>
            </form>
        </div>

        <!-- Tier List -->
        <div class="box col-span-12 lg:col-span-8 border border-n30 space-y-4">
            <h4 class="h4 bb-dashed pb-4">Configured Service Tiers</h4>
            @foreach ($tiers as $tier)
                @php
                    $prices = $tier->servicePrices->pluck('price', 'service_name');
                    $tierLower = strtolower(trim($tier->tier_name));
                    $tierMultiplier = (str_contains($tierLower, 'max') || str_contains($tierLower, 'platinum'))
                        ? '2.5x'
                        : (str_contains($tierLower, 'premium')
                            ? '2.9x'
                            : ((str_contains($tierLower, 'plus') && ! str_contains($tierLower, 'basic')) ? '3.5x' : '4x'));
                @endphp
                <div class="rounded-xl border border-n30 bg-primary/5 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h6 class="h6">{{ $tier->tier_name }}</h6>
                                <span class="inline-flex items-center rounded-full bg-primary/15 px-2.5 py-0.5 text-[11px] font-semibold text-primary">
                                    USSD AT {{ $tierMultiplier }}
                                </span>
                            </div>
                            <span class="text-xs text-n100">{{ $tier->clients_count }} assigned client(s) · SMS &amp; Airtime stay 1x</span>
                        </div>
                        @if ($tier->clients_count === 0)
                            <form method="POST" action="{{ route('admin.pricing-tiers.destroy', $tier) }}" onsubmit="return confirm('Delete this pricing tier?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-error py-1 px-3 text-xs">Delete</button>
                            </form>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('admin.pricing-tiers.update', $tier) }}" class="grid grid-cols-12 gap-3 items-end">
                        @csrf
                        @method('PUT')
                        <div class="col-span-4 md:col-span-3">
                            <label class="block text-xs mb-1">SMS Price</label>
                            <input type="number" step="0.01" min="0" name="sms_price" value="{{ $prices['SMS'] ?? 2.50 }}" required class="w-full rounded-xl border border-n30 bg-n0 px-3 py-2 text-xs" />
                        </div>
                        <div class="col-span-4 md:col-span-3">
                            <label class="block text-xs mb-1">USSD Price</label>
                            <input type="number" step="0.01" min="0" name="ussd_price" value="{{ $prices['USSD'] ?? 3.50 }}" required class="w-full rounded-xl border border-n30 bg-n0 px-3 py-2 text-xs" />
                        </div>
                        <div class="col-span-4 md:col-span-3">
                            <label class="block text-xs mb-1">Airtime Price</label>
                            <input type="number" step="0.01" min="0" name="airtime_price" value="{{ $prices['AIRTIME'] ?? 1.00 }}" required class="w-full rounded-xl border border-n30 bg-n0 px-3 py-2 text-xs" />
                        </div>
                        <div class="col-span-12 md:col-span-3">
                            <button type="submit" class="btn-secondary w-full justify-center py-2 px-3 text-xs">Update Prices</button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Telco Network Codes -->
    <div class="box border border-n30">
        <h4 class="h4 bb-dashed pb-4 mb-4">Telco USSD Interval Rates (<code>ussd_pricing</code>)</h4>
        <div class="grid grid-cols-12 gap-6">
            <form method="POST" action="{{ route('admin.telco-rates.store') }}" class="col-span-12 lg:col-span-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium mb-1">Network Code (MCCMNC)</label>
                    <input type="text" name="network_code" required placeholder="62130" class="w-full rounded-2xl border border-n30 bg-primary/5 px-4 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Network Name</label>
                    <input type="text" name="network_name" required placeholder="MTN Nigeria" class="w-full rounded-2xl border border-n30 bg-primary/5 px-4 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Tokens per Interval</label>
                    <input type="number" name="tokens_per_interval" min="0" required placeholder="15" class="w-full rounded-2xl border border-n30 bg-primary/5 px-4 py-2 text-sm" />
                </div>
                <button type="submit" class="btn-primary w-full justify-center text-sm">Save Telco Rate</button>
            </form>

            <div class="col-span-12 lg:col-span-8 overflow-x-auto">
                <table class="w-full whitespace-nowrap text-sm">
                    <thead>
                        <tr class="bg-secondary/5">
                            <th class="px-4 py-3 text-start">Network Code</th>
                            <th class="px-4 py-3 text-start">Network Name</th>
                            <th class="px-4 py-3 text-start">Tokens / Interval</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($telcoRates as $rate)
                            <tr class="even:bg-secondary/5">
                                <td class="px-4 py-2.5 font-mono font-medium">{{ $rate->network_code }}</td>
                                <td class="px-4 py-2.5">{{ $rate->network_name }}</td>
                                <td class="px-4 py-2.5 font-semibold text-primary">{{ $rate->tokens_per_interval }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-center text-n100">No telco rates configured.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
