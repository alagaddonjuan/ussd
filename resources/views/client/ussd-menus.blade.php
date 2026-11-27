@extends('layout.main')
@section('title', 'USSD Menus — ' . config('app.name'))

@section('content')
<div class="main-inner">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 lg:mb-8">
        <div>
            <h2 class="h2">USSD Menus</h2>
            <p class="text-sm text-n100 mt-1">Assigned Shortcode: <span class="font-semibold text-primary">{{ $client->ussd_code ?: 'Unassigned (ask an Admin to assign a code)' }}</span></p>
        </div>
        <a href="{{ route('export.ussd') }}" class="btn-outline"><i class="las la-download"></i> Export USSD Logs</a>
    </div>

    <div class="grid grid-cols-12 gap-6 mb-6">
        <div class="box col-span-12 lg:col-span-4 border border-n30">
            <h4 class="h4 bb-dashed pb-4 mb-4">Create New USSD Menu</h4>
            <form method="POST" action="{{ route('ussd.menus.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="menu_name" class="block font-medium mb-2">Menu Name</label>
                    <input type="text" name="menu_name" id="menu_name" required
                        class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-3 text-sm"
                        placeholder="e.g., Main Customer Menu" />
                </div>
                <button type="submit" class="btn-primary w-full justify-center">
                    <i class="las la-plus-circle"></i>
                    <span>Create Menu</span>
                </button>
            </form>
        </div>

        <div class="box col-span-12 lg:col-span-8 border border-n30">
            <h4 class="h4 bb-dashed pb-4 mb-4">Your Menus</h4>
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-sm">
                    <thead>
                        <tr class="bg-secondary/5">
                            <th class="px-4 py-3 text-start">Menu Name</th>
                            <th class="px-4 py-3 text-start">Steps</th>
                            <th class="px-4 py-3 text-start">Status</th>
                            <th class="px-4 py-3 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($menus as $menu)
                            <tr class="even:bg-secondary/5">
                                <td class="px-4 py-3 font-medium">{{ $menu->menu_name }}</td>
                                <td class="px-4 py-3">{{ $menu->items_count }} step(s)</td>
                                <td class="px-4 py-3">
                                    @if ($menu->is_active)
                                        <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">Active</span>
                                    @else
                                        <span class="rounded-full bg-secondary/10 px-3 py-1 text-xs font-medium text-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('ussd.menus.show', $menu) }}" class="btn-primary py-1.5 px-4 text-xs">
                                            Open Builder
                                        </a>
                                        @if (! $menu->is_active)
                                            <form method="POST" action="{{ route('ussd.menus.activate', $menu) }}">
                                                @csrf
                                                <button type="submit" class="btn-outline py-1.5 px-3 text-xs">Set Active</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('ussd.menus.archive', $menu) }}" onsubmit="return confirm('Archive this menu?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-error py-1.5 px-3 text-xs">Archive</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-n100">No USSD menus created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="box border border-n30">
        <div class="bb-dashed pb-4 mb-4 flex flex-wrap items-center justify-between gap-3">
            <h4 class="h4">Recent USSD Session Logs</h4>
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-n100" id="client-ussd-updated">Updated {{ now()->format('H:i:s') }}</span>
                <select id="client-ussd-auto-refresh" aria-label="Auto refresh USSD logs"
                    class="rounded-2xl border border-n30 bg-primary/5 px-2.5 py-1 text-xs font-medium">
                    <option value="0">Auto: Off</option>
                    <option value="5000">Auto: 5s</option>
                    <option value="10000" selected>Auto: 10s</option>
                    <option value="30000">Auto: 30s</option>
                </select>
                <button type="button" id="client-ussd-refresh-btn" class="btn-outline py-1 px-3 text-xs">
                    <i class="las la-sync" id="client-ussd-refresh-icon"></i>
                    <span>Refresh</span>
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap text-sm">
                <thead>
                    <tr class="bg-secondary/5">
                        <th class="px-4 py-3 text-start">Date</th>
                        <th class="px-4 py-3 text-start">Code</th>
                        <th class="px-4 py-3 text-start">Phone</th>
                        <th class="px-4 py-3 text-start">Carrier</th>
                        <th class="px-4 py-3 text-start">Hops</th>
                        <th class="px-4 py-3 text-start">Duration</th>
                        <th class="px-4 py-3 text-start">Input Path</th>
                        <th class="px-4 py-3 text-start">Cost</th>
                        <th class="px-4 py-3 text-start">Status</th>
                    </tr>
                </thead>
                <tbody id="client-ussd-logs-tbody">
                    @include('client.partials._ussd-logs-rows')
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('page-js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.getElementById('client-ussd-logs-tbody');
    const refreshBtn = document.getElementById('client-ussd-refresh-btn');
    const refreshIcon = document.getElementById('client-ussd-refresh-icon');
    const intervalSelect = document.getElementById('client-ussd-auto-refresh');
    const updatedLabel = document.getElementById('client-ussd-updated');
    const storageKey = 'q4_client_ussd_refresh_ms';
    let timerId = null;
    let busy = false;

    async function refreshLogs() {
        if (busy || !tbody) return;
        busy = true;
        refreshIcon && refreshIcon.classList.add('animate-spin');
        try {
            const res = await fetch(@json(route('ussd.logs.live')), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            tbody.innerHTML = data.html;
            if (updatedLabel && data.updated_at) updatedLabel.textContent = 'Updated ' + data.updated_at;
        } catch (e) {
            if (updatedLabel) updatedLabel.textContent = 'Sync retry pending';
        } finally {
            busy = false;
            refreshIcon && refreshIcon.classList.remove('animate-spin');
        }
    }

    function schedule() {
        if (timerId) clearInterval(timerId);
        const ms = parseInt(intervalSelect?.value || '0', 10);
        if (ms > 0) {
            timerId = setInterval(() => {
                if (!document.hidden) refreshLogs();
            }, ms);
        }
    }

    if (intervalSelect) {
        const saved = localStorage.getItem(storageKey);
        if (saved !== null) intervalSelect.value = saved;
        intervalSelect.addEventListener('change', () => {
            localStorage.setItem(storageKey, intervalSelect.value);
            schedule();
        });
        schedule();
    }

    refreshBtn && refreshBtn.addEventListener('click', refreshLogs);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && parseInt(intervalSelect?.value || '0', 10) > 0) refreshLogs();
    });
});
</script>
@endpush
