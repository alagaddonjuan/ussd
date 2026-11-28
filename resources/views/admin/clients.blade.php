@extends('layout.main')
@section('title', 'Manage Clients — ' . config('app.name'))

@section('content')
<div class="main-inner">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 lg:mb-8">
        <div>
            <h2 class="h2">Clients, Maintenance &amp; KYC</h2>
            <p class="text-sm text-n100 mt-1">Assign USSD shortcodes, tiers, KYC status, monthly maintenance fees (₦159,500), Perfex CRM sync, and cleanup test clients.</p>
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

    <!-- Perfex CRM Integration & Invoices Panel -->
    <div class="box border border-n30 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h5 class="h5">Perfex CRM Invoices ({{ $perfexUrl }})</h5>
                    @if ($perfexConfigured)
                        <span class="rounded-full bg-[#10B981]/15 px-2.5 py-0.5 text-xs font-semibold text-[#10B981]">Token Configured</span>
                    @else
                        <span class="rounded-full bg-[#F59E0B]/15 px-2.5 py-0.5 text-xs font-semibold text-[#F59E0B]">PERFEX_API_TOKEN Not Set</span>
                    @endif
                </div>
                <p class="text-xs text-n100 mt-1">
                    @if ($perfexConfigured)
                        Load existing invoices &amp; customer IDs from Perfex CRM, or click <strong>Sync Perfex Invoice</strong> on any client below.
                    @else
                        In Perfex CRM (<code>{{ $perfexUrl }}</code>) &rarr; <strong>API / Rest API &rarr; API Management</strong>, create a token with Customers &amp; Invoices permissions, then add <code>PERFEX_API_TOKEN=your_token</code> to <code>.env</code> and run <code>php artisan optimize:clear</code>.
                    @endif
                </p>
            </div>
            <button type="button" id="load-perfex-invoices-btn" class="btn-primary py-2 px-4 text-xs">
                <i class="las la-file-invoice"></i>
                <span>Test &amp; Load Perfex Invoices</span>
            </button>
        </div>

        <div id="perfex-invoices-panel" class="hidden mt-4 border-t border-n30 pt-4 space-y-4">
            <div id="perfex-status-banner" class="rounded-xl px-4 py-2.5 text-xs font-medium"></div>
            <div id="perfex-customers-strip" class="hidden text-xs">
                <span class="font-semibold text-n200">Perfex Customers (ID &rarr; Company):</span>
                <div id="perfex-customers-list" class="mt-1.5 flex flex-wrap gap-1.5"></div>
            </div>
            <div id="perfex-invoices-table-wrap" class="hidden overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-secondary/5">
                            <th class="px-3 py-2 text-start">Invoice #</th>
                            <th class="px-3 py-2 text-start">Customer</th>
                            <th class="px-3 py-2 text-start">Date</th>
                            <th class="px-3 py-2 text-start">Due Date</th>
                            <th class="px-3 py-2 text-start">Total</th>
                            <th class="px-3 py-2 text-start">Status</th>
                        </tr>
                    </thead>
                    <tbody id="perfex-invoices-tbody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Toolbar for Test Clients -->
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-n30 bg-n0 px-4 py-3">
        <div class="flex flex-wrap items-center gap-3 text-xs">
            <label class="inline-flex items-center gap-2 font-semibold cursor-pointer">
                <input type="checkbox" id="select-all-deletable-clients" />
                <span>Select All Non-Admin Clients on Page</span>
            </label>
            <button type="button" id="select-non-lottery-btn" class="btn-outline py-1 px-3 text-xs">
                Select All Except Modern Lottery
            </button>
        </div>
        <form method="POST" action="{{ route('admin.clients.bulk-delete') }}" id="bulk-delete-clients-form"
            onsubmit="return confirm('Permanently delete the selected test client(s) and all their menus, logs, API keys, and transactions? This cannot be undone.')">
            @csrf
            <div id="bulk-delete-hidden-inputs"></div>
            <button type="submit" id="bulk-delete-clients-btn" disabled
                class="btn-error py-1.5 px-4 text-xs opacity-50 cursor-not-allowed">
                <i class="las la-trash-alt"></i> Delete Selected (<span id="selected-clients-count">0</span>)
            </button>
        </form>
    </div>

    <div class="space-y-4">
        @forelse ($clients as $c)
            @php
                $isProtected = $c->isAdmin() || (int) $c->id === (int) auth()->id();
            @endphp
            <div class="box border border-n30">
                <div class="flex flex-wrap items-center justify-between gap-3 bb-dashed pb-3 mb-4">
                    <div class="flex items-start gap-3">
                        @if (! $isProtected)
                            <input type="checkbox"
                                class="mt-1.5 js-client-select"
                                value="{{ $c->id }}"
                                data-client-name="{{ strtolower($c->name) }}"
                                aria-label="Select {{ $c->name }}" />
                        @endif
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
                                title="{{ $perfexConfigured ? 'Create invoice in ' . $perfexUrl : 'Configure PERFEX_API_TOKEN in .env first' }}">
                                <i class="las la-file-invoice"></i> Sync Perfex Invoice
                            </button>
                        </form>
                        @if (! $isProtected)
                            <form method="POST" action="{{ route('admin.clients.destroy', $c) }}"
                                onsubmit="return confirm('Permanently delete {{ addslashes($c->name) }} ({{ addslashes($c->email) }}) and all their menus, logs, API keys, and transactions?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-error py-1.5 px-3 text-xs">
                                    <i class="las la-trash-alt"></i> Delete
                                </button>
                            </form>
                        @endif
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

@push('page-js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkboxes = Array.from(document.querySelectorAll('.js-client-select'));
    const selectAll = document.getElementById('select-all-deletable-clients');
    const selectNonLotteryBtn = document.getElementById('select-non-lottery-btn');
    const bulkBtn = document.getElementById('bulk-delete-clients-btn');
    const countSpan = document.getElementById('selected-clients-count');
    const hiddenWrap = document.getElementById('bulk-delete-hidden-inputs');

    function syncBulkSelection() {
        const selected = checkboxes.filter((cb) => cb.checked);
        if (countSpan) countSpan.textContent = String(selected.length);
        if (hiddenWrap) {
            hiddenWrap.innerHTML = '';
            selected.forEach((cb) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'client_ids[]';
                input.value = cb.value;
                hiddenWrap.appendChild(input);
            });
        }
        if (bulkBtn) {
            const disabled = selected.length === 0;
            bulkBtn.disabled = disabled;
            bulkBtn.classList.toggle('opacity-50', disabled);
            bulkBtn.classList.toggle('cursor-not-allowed', disabled);
        }
        if (selectAll && checkboxes.length > 0) {
            selectAll.checked = selected.length === checkboxes.length;
        }
    }

    checkboxes.forEach((cb) => cb.addEventListener('change', syncBulkSelection));

    selectAll && selectAll.addEventListener('change', () => {
        checkboxes.forEach((cb) => { cb.checked = selectAll.checked; });
        syncBulkSelection();
    });

    selectNonLotteryBtn && selectNonLotteryBtn.addEventListener('click', () => {
        checkboxes.forEach((cb) => {
            const name = cb.dataset.clientName || '';
            cb.checked = !name.includes('modern lottery');
        });
        syncBulkSelection();
    });

    // Perfex CRM Invoice Loader
    const loadPerfexBtn = document.getElementById('load-perfex-invoices-btn');
    const panel = document.getElementById('perfex-invoices-panel');
    const banner = document.getElementById('perfex-status-banner');
    const customersStrip = document.getElementById('perfex-customers-strip');
    const customersList = document.getElementById('perfex-customers-list');
    const tableWrap = document.getElementById('perfex-invoices-table-wrap');
    const tbody = document.getElementById('perfex-invoices-tbody');

    function esc(str) {
        const div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    loadPerfexBtn && loadPerfexBtn.addEventListener('click', async () => {
        panel && panel.classList.remove('hidden');
        if (banner) {
            banner.className = 'rounded-xl bg-primary/10 text-primary px-4 py-2.5 text-xs font-medium';
            banner.textContent = 'Connecting to Perfex CRM...';
        }
        try {
            const res = await fetch(@json(route('admin.perfex.invoices')), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const data = await res.json();

            if (banner) {
                if (data.invoices_ok) {
                    banner.className = 'rounded-xl bg-[#10B981]/15 text-[#10B981] px-4 py-2.5 text-xs font-medium';
                    banner.textContent = data.invoices_message + ' (' + (data.customers_message || '') + ')';
                } else {
                    banner.className = 'rounded-xl bg-error/15 text-error px-4 py-2.5 text-xs font-medium';
                    banner.textContent = data.invoices_message || data.customers_message || 'Failed to connect to Perfex CRM.';
                }
            }

            if (customersStrip && customersList && Array.isArray(data.customers) && data.customers.length > 0) {
                customersStrip.classList.remove('hidden');
                customersList.innerHTML = data.customers.map((c) => {
                    const id = c.userid || c.id || '';
                    const name = c.company || c.name || 'Customer';
                    return `<span class="rounded-full border border-n30 bg-primary/5 px-2.5 py-0.5 font-mono">#${esc(id)} ${esc(name)}</span>`;
                }).join('');
            }

            if (tableWrap && tbody) {
                if (Array.isArray(data.invoices) && data.invoices.length > 0) {
                    tableWrap.classList.remove('hidden');
                    tbody.innerHTML = data.invoices.map((inv) => `
                        <tr class="even:bg-secondary/5">
                            <td class="px-3 py-2 font-semibold">${esc(inv.number)}</td>
                            <td class="px-3 py-2">${esc(inv.company)}</td>
                            <td class="px-3 py-2 text-n100">${esc(inv.date)}</td>
                            <td class="px-3 py-2 text-n100">${esc(inv.duedate)}</td>
                            <td class="px-3 py-2 font-semibold">${Number(inv.total || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                            <td class="px-3 py-2">${esc(inv.status)}</td>
                        </tr>
                    `).join('');
                } else {
                    tableWrap.classList.add('hidden');
                }
            }
        } catch (err) {
            if (banner) {
                banner.className = 'rounded-xl bg-error/15 text-error px-4 py-2.5 text-xs font-medium';
                banner.textContent = 'Request failed while checking Perfex CRM.';
            }
        }
    });
});
</script>
@endpush
