@extends('layout.main')
@section('title', 'Manage Clients — ' . config('app.name'))

@section('content')
<div class="main-inner">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 lg:mb-8">
        <div>
            <h2 class="h2">Clients &amp; Wallets</h2>
            <p class="text-sm text-n100 mt-1">Assign USSD shortcodes, pricing tiers, account statuses, and manual wallet credits/debits.</p>
        </div>
        <form method="GET" action="{{ route('admin.clients') }}" class="flex gap-2">
            <input type="text" name="q" value="{{ $search }}" placeholder="Search name, email, USSD..."
                class="rounded-3xl border border-n30 bg-n0 px-4 py-2 text-sm min-w-[240px]" />
            <button type="submit" class="btn-primary py-2 px-5 text-sm">Search</button>
        </form>
    </div>

    <div class="space-y-4">
        @forelse ($clients as $c)
            <div class="box border border-n30">
                <div class="flex flex-wrap items-center justify-between gap-3 bb-dashed pb-3 mb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h5 class="h5">{{ $c->name }}</h5>
                            @if ($c->is_admin)
                                <span class="rounded-full bg-secondary/10 px-2.5 py-0.5 text-xs font-medium text-secondary">Admin</span>
                            @endif
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $c->status === 'active' ? 'bg-primary/10 text-primary' : 'bg-error/10 text-error' }}">
                                {{ ucfirst($c->status) }}
                            </span>
                        </div>
                        <p class="text-xs text-n100 mt-0.5">{{ $c->email }} &bull; Balance: <strong class="text-primary">₦{{ number_format((float) $c->token_balance, 2) }}</strong></p>
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-4">
                    <!-- Config Update -->
                    <form method="POST" action="{{ route('admin.clients.update', $c) }}" class="col-span-12 xl:col-span-7 grid grid-cols-12 gap-3 items-end">
                        @csrf
                        @method('PUT')
                        <div class="col-span-6 md:col-span-3">
                            <label class="block text-xs font-medium mb-1">Status</label>
                            <select name="status" class="w-full rounded-xl border border-n30 bg-primary/5 px-3 py-2 text-xs">
                                <option value="active" @selected($c->status === 'active')>Active</option>
                                <option value="pending" @selected($c->status === 'pending')>Pending</option>
                                <option value="suspended" @selected($c->status === 'suspended')>Suspended</option>
                            </select>
                        </div>
                        <div class="col-span-6 md:col-span-3">
                            <label class="block text-xs font-medium mb-1">USSD Code</label>
                            <input type="text" name="ussd_code" value="{{ $c->ussd_code }}" placeholder="*347*102#"
                                class="w-full rounded-xl border border-n30 bg-primary/5 px-3 py-2 text-xs" />
                        </div>
                        <div class="col-span-6 md:col-span-2">
                            <label class="block text-xs font-medium mb-1">Sender ID</label>
                            <input type="text" name="sender_id" value="{{ $c->sender_id }}" placeholder="Sender"
                                class="w-full rounded-xl border border-n30 bg-primary/5 px-3 py-2 text-xs" />
                        </div>
                        <div class="col-span-6 md:col-span-2">
                            <label class="block text-xs font-medium mb-1">Tier</label>
                            <select name="pricing_tier_id" class="w-full rounded-xl border border-n30 bg-primary/5 px-3 py-2 text-xs">
                                <option value="">None</option>
                                @foreach ($tiers as $t)
                                    <option value="{{ $t->id }}" @selected($c->pricing_tier_id == $t->id)>{{ $t->tier_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-12 md:col-span-2">
                            <button type="submit" class="btn-primary w-full justify-center py-2 px-3 text-xs">Save</button>
                        </div>
                    </form>

                    <!-- Manual Credit/Debit -->
                    <form method="POST" action="{{ route('admin.clients.transaction', $c) }}" class="col-span-12 xl:col-span-5 grid grid-cols-12 gap-3 items-end border-t xl:border-t-0 xl:border-l border-n30 pt-3 xl:pt-0 xl:pl-4">
                        @csrf
                        <div class="col-span-4">
                            <label class="block text-xs font-medium mb-1">Action</label>
                            <select name="type" class="w-full rounded-xl border border-n30 bg-secondary/5 px-3 py-2 text-xs">
                                <option value="credit">Credit (+)</option>
                                <option value="debit">Debit (-)</option>
                            </select>
                        </div>
                        <div class="col-span-4">
                            <label class="block text-xs font-medium mb-1">Amount</label>
                            <input type="number" name="amount" min="1" step="1" required placeholder="1000"
                                class="w-full rounded-xl border border-n30 bg-secondary/5 px-3 py-2 text-xs" />
                        </div>
                        <div class="col-span-4">
                            <button type="submit" class="btn-secondary w-full justify-center py-2 px-3 text-xs">Apply</button>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <div class="box border border-n30 text-center py-10 text-n100">No clients matched your search.</div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $clients->withQueryString()->links() }}
    </div>
</div>
@endsection
