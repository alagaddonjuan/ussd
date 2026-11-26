@extends('layout.main')
@section('title', 'USSD Tree Builder — ' . $menu->menu_name)

@section('content')
<div class="main-inner">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 lg:mb-8">
        <div>
            <h2 class="h2">{{ $menu->menu_name }}</h2>
            <p class="text-sm text-n100 mt-1">
                Owner: <span class="font-medium">{{ $menu->client?->name }}</span>
                | Code: <span class="font-semibold text-primary">{{ $menu->client?->ussd_code ?: 'Unassigned' }}</span>
                | Status: <span class="font-semibold">{{ $menu->is_active ? 'Active' : 'Inactive' }}</span>
            </p>
        </div>
        <div class="flex gap-3">
            @if (! $menu->is_active)
                <form method="POST" action="{{ route('ussd.menus.activate', $menu) }}">
                    @csrf
                    <button type="submit" class="btn-primary"><i class="las la-check"></i> Activate Menu</button>
                </form>
            @endif
            <a href="{{ route('ussd.menus.index') }}" class="btn-outline">Back to Menus</a>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6">
        <!-- Menu Tree & Add Step -->
        <div class="col-span-12 xl:col-span-8 space-y-6">
            <div class="box border border-n30">
                <h4 class="h4 bb-dashed pb-4 mb-4">Add Menu Step</h4>
                <form method="POST" action="{{ route('ussd.items.store', $menu) }}" class="grid grid-cols-12 gap-4">
                    @csrf
                    <div class="col-span-12 md:col-span-5">
                        <label for="parent_item_id" class="block text-sm font-medium mb-1">Parent Step</label>
                        <select name="parent_item_id" id="parent_item_id" class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-2.5 text-sm">
                            <option value="">-- Top Level (ROOT) --</option>
                            @foreach ($items as $opt)
                                <option value="{{ $opt->id }}">[#{{ $opt->id }}] {{ $opt->option_trigger }} — {{ \Illuminate\Support\Str::limit($opt->displayBody(), 35) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-6 md:col-span-3">
                        <label for="option_trigger" class="block text-sm font-medium mb-1">Trigger Key</label>
                        <input type="text" name="option_trigger" id="option_trigger" required
                            class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-2.5 text-sm"
                            placeholder="ROOT, 1, 2..." />
                    </div>
                    <div class="col-span-6 md:col-span-4">
                        <label for="response_type" class="block text-sm font-medium mb-1">Action Type</label>
                        <select name="response_type" id="response_type" class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-2.5 text-sm">
                            <option value="CON">CON (Continue / Submenu)</option>
                            <option value="END">END (Terminate Session)</option>
                            <option value="API">API (Forward to External API)</option>
                        </select>
                    </div>
                    <div class="col-span-12">
                        <label for="response_text" class="block text-sm font-medium mb-1">Screen Text (supports optional Label|Body format)</label>
                        <textarea name="response_text" id="response_text" rows="3" required
                            class="w-full rounded-2xl border border-n30 bg-primary/5 px-4 py-2.5 text-sm"
                            placeholder="Welcome!&#10;1. Play Games&#10;2. Check Balance"></textarea>
                    </div>
                    <div class="col-span-12">
                        <button type="submit" class="btn-primary"><i class="las la-plus"></i> Add Step</button>
                    </div>
                </form>
            </div>

            <div class="box border border-n30">
                <h4 class="h4 bb-dashed pb-4 mb-4">Menu Flow Tree ({{ $items->count() }} steps)</h4>
                @if (count($tree) > 0)
                    <div class="space-y-3">
                        @foreach ($tree as $node)
                            @include('client.partials._menu-node', ['node' => $node, 'items' => $items, 'depth' => 0])
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-n100 py-4">No steps in this menu yet. Add a top-level ROOT item above.</p>
                @endif
            </div>
        </div>

        <!-- Live USSD Phone Simulator -->
        <div class="col-span-12 xl:col-span-4">
            <div class="box border border-n30 sticky top-24">
                <h4 class="h4 bb-dashed pb-4 mb-4">Live USSD Simulator</h4>
                <p class="text-xs text-n100 mb-4">Test your USSD flow in sandbox mode without deducting tokens. Use <strong>0</strong> to go back.</p>

                <div class="rounded-3xl border-4 border-n700 bg-n900 p-4 text-white shadow-lg">
                    <div class="flex items-center justify-between text-xs text-n100 mb-3">
                        <span>{{ $menu->client?->ussd_code ?: '*384*23#' }}</span>
                        <span id="sim-badge" class="rounded bg-primary/20 px-2 py-0.5 text-primary">IDLE</span>
                    </div>
                    <pre id="sim-screen" class="min-h-[160px] whitespace-pre-wrap font-mono text-sm leading-relaxed text-white">Press "Dial Code" to start a USSD session.</pre>
                    <div class="mt-4 flex gap-2">
                        <input type="text" id="sim-input" placeholder="Option (e.g. 1)" disabled
                            class="w-full rounded-xl bg-n800 border border-n600 px-3 py-2 text-sm text-white" />
                        <button type="button" id="sim-send-btn" disabled class="btn-primary py-2 px-4 text-xs">Send</button>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button type="button" id="sim-dial-btn" class="btn-secondary w-full justify-center py-2 text-xs">
                            <i class="las la-phone"></i> Dial Code
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('page-js')
<script>
    (function () {
        const dialBtn = document.getElementById('sim-dial-btn');
        const sendBtn = document.getElementById('sim-send-btn');
        const inputEl = document.getElementById('sim-input');
        const screenEl = document.getElementById('sim-screen');
        const badgeEl = document.getElementById('sim-badge');
        const simUrl = @json(route('ussd.menus.simulate', $menu));
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

        let history = [];

        async function runStep(rawText) {
            screenEl.textContent = 'Connecting...';
            try {
                const res = await fetch(simUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ text: rawText })
                });
                const data = await res.json();
                const reply = data.reply || 'END No response';
                const isCon = reply.startsWith('CON ');
                screenEl.textContent = reply.replace(/^(CON|END)\s+/, '');
                badgeEl.textContent = isCon ? 'CON (Active)' : 'END (Closed)';
                inputEl.disabled = !isCon;
                sendBtn.disabled = !isCon;
                if (isCon) inputEl.focus();
            } catch (err) {
                screenEl.textContent = 'Simulator request failed.';
                inputEl.disabled = true;
                sendBtn.disabled = true;
            }
        }

        dialBtn.addEventListener('click', function () {
            history = [];
            inputEl.value = '';
            runStep('');
        });

        function submitChoice() {
            const val = inputEl.value.trim();
            if (!val) return;
            history.push(val);
            inputEl.value = '';
            runStep(history.join('*'));
        }

        sendBtn.addEventListener('click', submitChoice);
        inputEl.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitChoice();
            }
        });
    })();

    function selectParentStep(parentId) {
        const select = document.getElementById('parent_item_id');
        if (select) {
            select.value = String(parentId);
            select.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
</script>
@endpush
