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
        <h4 class="h4 bb-dashed pb-4 mb-4">Recent USSD Session Logs</h4>
        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap text-sm">
                <thead>
                    <tr class="bg-secondary/5">
                        <th class="px-4 py-3 text-start">Session ID</th>
                        <th class="px-4 py-3 text-start">Phone</th>
                        <th class="px-4 py-3 text-start">Code</th>
                        <th class="px-4 py-3 text-start">Input Path</th>
                        <th class="px-4 py-3 text-start">Cost</th>
                        <th class="px-4 py-3 text-start">Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ussdLogs as $log)
                        <tr class="even:bg-secondary/5">
                            <td class="px-4 py-2.5 text-xs">{{ \Illuminate\Support\Str::limit($log->session_id, 20) }}</td>
                            <td class="px-4 py-2.5 font-medium">{{ $log->phone_number }}</td>
                            <td class="px-4 py-2.5">{{ $log->service_code }}</td>
                            <td class="px-4 py-2.5">{{ $log->session_data }}</td>
                            <td class="px-4 py-2.5">₦{{ number_format((float) $log->cost, 2) }}</td>
                            <td class="px-4 py-2.5 text-xs text-n100">{{ optional($log->created_at)->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-n100">No USSD logs recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
