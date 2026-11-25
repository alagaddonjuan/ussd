@php
    $item = $node['item'];
    $children = $node['children'];
    $badgeClass = match ($item->response_type) {
        'END' => 'bg-error/10 text-error',
        'API' => 'bg-secondary/10 text-secondary',
        default => 'bg-primary/10 text-primary',
    };
@endphp
<div class="rounded-xl border border-n30 p-3 bg-primary/5" style="margin-left: {{ min($depth * 18, 90) }}px">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="space-y-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-n0 border border-n30 px-2.5 py-0.5 text-xs font-semibold">#{{ $item->id }}</span>
                <span class="rounded-full bg-n900 text-white px-2.5 py-0.5 text-xs font-semibold">Trigger: {{ $item->option_trigger }}</span>
                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeClass }}">{{ $item->response_type }}</span>
            </div>
            <p class="text-sm whitespace-pre-line font-mono pt-1">{{ $item->response_text }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="selectParentStep({{ $item->id }})" class="btn-outline py-1 px-3 text-xs">
                + Child
            </button>
            <button type="button" onclick="document.getElementById('edit-node-{{ $item->id }}').classList.toggle('hidden')" class="btn-secondary py-1 px-3 text-xs">
                Edit
            </button>
            <form method="POST" action="{{ route('ussd.items.destroy', $item) }}" onsubmit="return confirm('Delete this step and any child steps?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-error py-1 px-3 text-xs">Delete</button>
            </form>
        </div>
    </div>

    <form id="edit-node-{{ $item->id }}" method="POST" action="{{ route('ussd.items.update', $item) }}" class="hidden mt-3 pt-3 border-t border-n30 grid grid-cols-12 gap-3">
        @csrf
        @method('PUT')
        <div class="col-span-12 md:col-span-4">
            <select name="parent_item_id" class="w-full rounded-xl border border-n30 bg-n0 px-3 py-2 text-xs">
                <option value="">-- Top Level --</option>
                @foreach ($items as $opt)
                    @if ($opt->id !== $item->id)
                        <option value="{{ $opt->id }}" @selected($item->parent_item_id == $opt->id)>#{{ $opt->id }} ({{ $opt->option_trigger }})</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="col-span-6 md:col-span-4">
            <input type="text" name="option_trigger" value="{{ $item->option_trigger }}" required
                class="w-full rounded-xl border border-n30 bg-n0 px-3 py-2 text-xs" />
        </div>
        <div class="col-span-6 md:col-span-4">
            <select name="response_type" class="w-full rounded-xl border border-n30 bg-n0 px-3 py-2 text-xs">
                <option value="CON" @selected($item->response_type === 'CON')>CON</option>
                <option value="END" @selected($item->response_type === 'END')>END</option>
                <option value="API" @selected($item->response_type === 'API')>API</option>
            </select>
        </div>
        <div class="col-span-12">
            <textarea name="response_text" rows="2" required class="w-full rounded-xl border border-n30 bg-n0 px-3 py-2 text-xs">{{ $item->response_text }}</textarea>
        </div>
        <div class="col-span-12">
            <button type="submit" class="btn-primary py-1.5 px-4 text-xs">Save Changes</button>
        </div>
    </form>
</div>

@foreach ($children as $child)
    @include('client.partials._menu-node', ['node' => $child, 'items' => $items, 'depth' => $depth + 1])
@endforeach
