@extends('layout.main')
@section('title', 'SMS & Airtime Services — ' . config('app.name'))

@section('content')
<div class="main-inner">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 lg:mb-8">
        <div>
            <h2 class="h2">SMS &amp; Airtime Services</h2>
            <p class="text-sm text-n100 mt-1">Dispatch SMS messages and vend airtime directly from your token wallet (Balance: ₦{{ number_format((float) $client->token_balance, 2) }})</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('export.sms') }}" class="btn-outline"><i class="las la-download"></i> Export SMS CSV</a>
            <a href="{{ route('export.airtime') }}" class="btn-outline"><i class="las la-download"></i> Export Airtime CSV</a>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6 mb-6">
        <!-- Send SMS -->
        <div class="box col-span-12 lg:col-span-6 border border-n30">
            <h4 class="h4 bb-dashed pb-4 mb-4">Send SMS <span class="text-sm font-normal text-n100">(₦{{ number_format($smsRate, 2) }} / 160 chars)</span></h4>
            <form method="POST" action="{{ route('services.sms.send') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="to" class="block font-medium mb-2">Recipient Phone Number</label>
                    <input type="text" name="to" id="to" value="{{ old('to') }}" required
                        class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-3 text-sm"
                        placeholder="+2348012345678" />
                </div>
                <div>
                    <label for="message" class="block font-medium mb-2">Message</label>
                    <textarea name="message" id="message" rows="4" required
                        class="w-full rounded-2xl border border-n30 bg-primary/5 px-4 py-3 text-sm"
                        placeholder="Enter your SMS content...">{{ old('message') }}</textarea>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="las la-paper-plane"></i>
                    <span>Send SMS</span>
                </button>
            </form>
        </div>

        <!-- Send Airtime -->
        <div class="box col-span-12 lg:col-span-6 border border-n30" id="airtime">
            <h4 class="h4 bb-dashed pb-4 mb-4">Send Airtime</h4>
            <form method="POST" action="{{ route('services.airtime.send') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="phone_number" class="block font-medium mb-2">Recipient Phone Number</label>
                    <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number') }}" required
                        class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-3 text-sm"
                        placeholder="+2348012345678" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="network" class="block font-medium mb-2">Network</label>
                        <select name="network" id="network" class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-3 text-sm">
                            <option value="MTN">MTN</option>
                            <option value="Airtel">Airtel</option>
                            <option value="GLO">GLO</option>
                            <option value="9mobile">9mobile</option>
                        </select>
                    </div>
                    <div>
                        <label for="amount" class="block font-medium mb-2">Amount (NGN)</label>
                        <input type="number" name="amount" id="amount" min="50" max="50000" step="1" value="{{ old('amount', 100) }}" required
                            class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-3 text-sm" />
                    </div>
                </div>
                <button type="submit" class="btn-secondary">
                    <i class="las la-mobile-alt"></i>
                    <span>Vend Airtime</span>
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6">
        <div class="box col-span-12 lg:col-span-6 border border-n30">
            <h4 class="h4 bb-dashed pb-4 mb-4">Recent SMS Logs</h4>
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-sm">
                    <thead>
                        <tr class="bg-secondary/5">
                            <th class="px-4 py-3 text-start">Recipient</th>
                            <th class="px-4 py-3 text-start">Message</th>
                            <th class="px-4 py-3 text-start">Cost</th>
                            <th class="px-4 py-3 text-start">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($smsLogs as $log)
                            <tr class="even:bg-secondary/5">
                                <td class="px-4 py-2.5 font-medium">{{ $log->phone_number }}</td>
                                <td class="px-4 py-2.5">{{ \Illuminate\Support\Str::limit($log->message, 32) }}</td>
                                <td class="px-4 py-2.5">₦{{ number_format((float) $log->cost, 2) }}</td>
                                <td class="px-4 py-2.5"><span class="rounded-full bg-primary/10 px-3 py-1 text-xs text-primary">{{ $log->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-n100">No SMS logs yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="box col-span-12 lg:col-span-6 border border-n30">
            <h4 class="h4 bb-dashed pb-4 mb-4">Recent Airtime Logs</h4>
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-sm">
                    <thead>
                        <tr class="bg-secondary/5">
                            <th class="px-4 py-3 text-start">Phone</th>
                            <th class="px-4 py-3 text-start">Network</th>
                            <th class="px-4 py-3 text-start">Amount</th>
                            <th class="px-4 py-3 text-start">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($airtimeLogs as $log)
                            <tr class="even:bg-secondary/5">
                                <td class="px-4 py-2.5 font-medium">{{ $log->phone_number }}</td>
                                <td class="px-4 py-2.5">{{ $log->network }}</td>
                                <td class="px-4 py-2.5">₦{{ number_format((float) $log->amount, 2) }}</td>
                                <td class="px-4 py-2.5"><span class="rounded-full bg-primary/10 px-3 py-1 text-xs text-primary">{{ $log->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-n100">No airtime logs yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
