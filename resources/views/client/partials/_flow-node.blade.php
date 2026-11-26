@php
    $item = $node['item'];
    $children = $node['children'] ?? [];
    $badgeClass = match ($item->response_type) {
        'CON' => 'bg-[#0878F8]/15 text-[#0878F8] border-[#0878F8]/30',
        'API' => 'bg-[#6D5EF7]/15 text-[#6D5EF7] border-[#6D5EF7]/30',
        default => 'bg-[#10B981]/15 text-[#10B981] border-[#10B981]/30',
    };
    $firstLine = strtok(str_replace(["\r\n", "\r"], "\n", $item->displayBody()), "\n") ?: $item->displayBody();
@endphp
<div class="{{ $depth > 0 ? 'ml-5 pl-4 border-l-2 border-[#0878F8]/30' : '' }} space-y-2">
    <div class="rounded-2xl border border-[#D1D5DB]/70 dark:border-white/15 bg-white/75 dark:bg-slate-900/60 px-3.5 py-2.5 shadow-xs flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2 min-w-0">
            <span class="rounded-lg bg-[#0B3A75] px-2 py-0.5 font-mono text-[11px] font-bold text-white shrink-0">
                {{ $item->option_trigger }}
            </span>
            <span class="rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase shrink-0 {{ $badgeClass }}">
                {{ $item->response_type }}
            </span>
            <span class="text-xs font-medium text-[#1F2937] dark:text-slate-200 truncate">
                {{ $item->displayTitle() ?: \Illuminate\Support\Str::limit($firstLine, 55) }}
            </span>
        </div>
        <span class="text-[11px] text-n100 font-mono shrink-0">#{{ $item->id }}</span>
    </div>

    @if (count($children) > 0)
        <div class="space-y-2">
            @foreach ($children as $child)
                @include('client.partials._flow-node', ['node' => $child, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</div>
