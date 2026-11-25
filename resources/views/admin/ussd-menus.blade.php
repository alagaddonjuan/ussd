@extends('layout.main')
@section('title', 'All Client USSD Menus — ' . config('app.name'))

@section('content')
<div class="main-inner">
    <div class="mb-6 lg:mb-8">
        <h2 class="h2">All Client USSD Menus</h2>
    </div>

    <div class="box border border-n30">
        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap text-sm">
                <thead>
                    <tr class="bg-secondary/5">
                        <th class="px-4 py-3 text-start">ID</th>
                        <th class="px-4 py-3 text-start">Client</th>
                        <th class="px-4 py-3 text-start">USSD Code</th>
                        <th class="px-4 py-3 text-start">Menu Name</th>
                        <th class="px-4 py-3 text-start">Steps</th>
                        <th class="px-4 py-3 text-start">Status</th>
                        <th class="px-4 py-3 text-end">Builder</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($menus as $menu)
                        <tr class="even:bg-secondary/5">
                            <td class="px-4 py-3">#{{ $menu->id }}</td>
                            <td class="px-4 py-3 font-medium">{{ $menu->client?->name ?? 'Deleted Client' }}</td>
                            <td class="px-4 py-3 font-mono text-primary">{{ $menu->client?->ussd_code ?: 'Unassigned' }}</td>
                            <td class="px-4 py-3">{{ $menu->menu_name }}</td>
                            <td class="px-4 py-3">{{ $menu->items_count }}</td>
                            <td class="px-4 py-3">
                                @if ($menu->is_archived)
                                    <span class="rounded-full bg-n30 px-3 py-1 text-xs">Archived</span>
                                @elseif ($menu->is_active)
                                    <span class="rounded-full bg-primary/10 px-3 py-1 text-xs text-primary">Active</span>
                                @else
                                    <span class="rounded-full bg-secondary/10 px-3 py-1 text-xs text-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-end">
                                <a href="{{ route('ussd.menus.show', $menu) }}" class="btn-primary py-1.5 px-4 text-xs">Inspect / Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-n100">No USSD menus found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
