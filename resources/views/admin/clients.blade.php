@extends('layout.main')
@section('title', 'Manage Clients — ' . config('app.name'))

@section('content')
<div class="main-inner">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 lg:mb-8">
        <div>
            <h2 class="h2">Clients, Maintenance &amp; KYC</h2>
            <p class="text-sm text-n100 mt-1">Assign USSD shortcodes, tiers, KYC status, monthly maintenance fees (₦159,500), and manual credits/debits.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <form method="GET" action="{{ route('admin.clients') }}" class="flex gap-2">
                <input type="text" name="q" value="{{ $search }}" placeholder="Search name, email, USSD..."
                    class="rounded-3xl border border-n30 bg-n0 px-4 py-2 text-sm min-w-[220px]" />
                <button type="submit" class="btn-primary py-2 px-5 text-sm">Search</button>
            </form>
            <form method="POST" action="{{ route('admin.clients.bill-maintenance') }}" onsubmit="return confirm('Collect due monthly maintenance fees for active clients now?')">
                @csrf
                <button type="submit" class="btn-secondary py-2 px-4 text-xs">
                    <i class="las la-file-invoice-dollar"></i> Bill Monthly Maintenance
                </button>
            </form>
        </div>
    </div>

    <div class="space-y-4">
        @forelse ($clients as $c)
            <div class="box border border-n30">
                <div class="flex flex-wrap items-center justify-between gap-3 bb-dashed pb-3 mb-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h5 class="h5">{{ $c->name }}</h5>
                            @if ($c->is_admin)
                                <span class="rounded-full bg-secondary/10 px-2.5 py-0.5 text-xs font-medium text-secondary">Admin</span>
                            @endif
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $c->status === 'active' ? 'bg-[#10B981]/15 text-[#10B981]' : 'bg-error/10 text-error' }}">
                                {{ ucfirst($c->status) }}
                            </span>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ ($c->kyc_status ?? 'unverified') === 'approved' ? 'bg-primary/15 text-primary' : 'bg-warning/15 text-warning' }}">
                                KYC: {{ ucfirst($c->kyc_status ?? 'unverified') }}
                            </span>
                        </div>
                        <p class="text-xs text-n100 mt-0.5">
                            {{ $c->email }} &bull;
                            Balance: <strong class="text-primary">₦{{ number_format((float) $c->token_balance, 2) }}</strong> &bull;
                            Maint: ₦{{ number_format((float) ($c->monthly_maintenance_fee ?? 159500), 2) }}
                            @if ($c->last_maintenance_billed_at)
                                (Last: {{ $c->last_maintenance_billed_at->format('M d') }})
                            @endif
                            @if ($c->perfex_client_id)
                                &bull; Perfex #{{ $c->perfex_client_id }}
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if ($c->kyc_document_path)
                            <a href="{{ route('admin.clients.kyc.download', $c) }}" class="btn-outline py-1.5 px-3 text-xs">
                                <i class="las la-file-download"></i> View KYC Doc
                            </a>
                        @endif
                        <form method="POST" action="{{ route('admin.clients.perfex-invoice', $c) }}">
                            @csrf
                            <button type="submit" class="btn-secondary py-1.5 px-3 text-xs"
                                title="{{ $perfexConfigured ? 'Create invoice in crm.q4iltd.com' : 'Configure PERFEX_API_TOKEN in .env first' }}">
                                <i class="las la-file-invoice"></i> Sync Perfex Invoice
                            </button>
                        </form>
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-4">
                    <!-- Config Update -->
                    <form method="POST" action="{{ route('admin.clients.update', $c) }}" class="col-span-12 xl:col-span-8 grid grid-cols-12 gap-3 items-end">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="maintenance_enabled_present" value="1" />
                        <div class="col-span-6 md:col-span-2">
                            <label class="block text-xs font-medium mb-1">Status</label>
                            <select name="status" class="w-full rounded-xl border border-n30 bg-primary/5 px-2.5 py-2 text-xs">
                                <option value="active" @selected($c->status === 'active')>Active</option>
                                <option value="pending" @selected($c->status === 'pending')>Pending</option>
                                <option value="suspended" @selected($c->status === 'suspended')>Suspended</option>
                            </select>
                        </div>
                        <div class="col-span-6 md:col-span-2">
                            <label class="block text-xs font-medium mb-1">USSD Code</label>
                            <input type="text" name="ussd_code" value="{{ $c->ussd_code }}" placeholder="*384*23#"
                                class="w-full rounded-xl border border-n30 bg-primary/5 px-2.5 py-2 text-xs" />
                        </div>
                        <div class="col-span-6 md:col-span-2">
                            <label class="block text-xs font-medium mb-1">Sender ID</label>
                            <input type="text" name="sender_id" value="{{ $c->sender_id }}" placeholder="Sender"
                                class="w-full rounded-xl border border-n30 bg-primary/5 px-2.5 py-2 text-xs" />
                        </div>
                        <div class="col-span-6 md:col-span-2">
                            <label class="block text-xs font-medium mb-1">Tier</label>
                            <select name="pricing_tier_id" class="w-full rounded-xl border border-n30 bg-primary/5 px-2.5 py-2 text-xs">
                                <option value="">None</option>
                                @foreach ($tiers as $t)
                                    <option value="{{ $t->id }}" @selected($c->pricing_tier_id == $t->id)>{{ $t->tier_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-6 md:col-span-2">
                            <label class="block text-xs font-medium mb-1">KYC</label>
                            <select name="kyc_status" class="w-full rounded-xl border border-n30 bg-primary/5 px-2.5 py-2 text-xs">
                                <option value="unverified" @selected(($c->kyc_status ?? 'unverified') === 'unverified')>Unverified</option>
                                <option value="pending" @selected($c->kyc_status === 'pending')>Pending</option>
                                <option value="approved" @selected($c->kyc_status === 'approved')>Approved</option>
                                <option value="rejected" @selected($c->kyc_status === 'rejected')>Rejected</option>
                            </select>
                        </div>
                        <div class="col-span-6 md:col-span-2">
                            <label class="block text-xs font-medium mb-1">Maint. Fee (₦)</label>
                            <input type="number" step="100" min="0" name="monthly_maintenance_fee" value="{{ $c->monthly_maintenance_fee ?? 159500 }}"
                                class="w-full rounded-xl border border-n30 bg-primary/5 px-2.5 py-2 text-xs" />
                        </div>
                        <div class="col-span-8 md:col-span-8 flex flex-wrap items-center gap-3">
                            <label class="inline-flex items-center gap-1.5 text-xs cursor-pointer">
                                <input type="checkbox" name="maintenance_enabled" value="1" @checked($c->maintenance_enabled ?? true) />
                                <span>Monthly Maint. Active</span>
                            </label>
                            <input type="number" min="1" name="perfex_client_id" value="{{ $c->perfex_client_id }}" placeholder="Perfex ID"
                                class="w-24 rounded-xl border border-n30 bg-primary/5 px-2.5 py-1.5 text-xs" />
                            <input type="text" name="kyc_notes" value="{{ $c->kyc_notes }}" placeholder="KYC note (CAC / ID ref)"
                                class="flex-1 min-w-[140px] rounded-xl border border-n30 bg-primary/5 px-2.5 py-1.5 text-xs" />
                        </div>
                        <div class="col-span-4 md:col-span-4">
                            <button type="submit" class="btn-primary w-full justify-center py-2 px-3 text-xs">Save Client</button>
                        </div>
                    </form>

                    <!-- Manual Credit/Debit -->
                    <form method="POST" action="{{ route('admin.clients.transaction', $c) }}" class="col-span-12 xl:col-span-4 grid grid-cols-12 gap-2 items-end border-t xl:border-t-0 xl:border-l border-n30 pt-3 xl:pt-0 xl:pl-4">
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
