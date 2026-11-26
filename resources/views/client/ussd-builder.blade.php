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
                | Status: <span class="font-semibold {{ $menu->is_active ? 'text-[#10B981]' : 'text-n200' }}">{{ $menu->is_active ? 'Active' : 'Inactive' }}</span>
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
        <!-- Menu Text Builder, Single Step Builder & Tree -->
        <div class="col-span-12 xl:col-span-8 space-y-6">
            <!-- Quick Text-to-Menu Builder -->
            <div class="box border border-n30">
                <div class="bb-dashed pb-4 mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h4 class="h4">Quick Text-to-Menu Builder</h4>
                        <p class="text-xs text-n100 mt-1">
                            Type an indented outline to generate multi-level USSD trees in seconds. Indent sub-items with 2 spaces; add optional <code>[API: Text]</code> or <code>[END: Text]</code>.
                        </p>
                    </div>
                    <span id="text-parse-badge" class="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                        Ready
                    </span>
                </div>

                <div class="flex flex-wrap gap-2 mb-3">
                    <button type="button" id="preset-lottery-btn" class="btn-outline py-1.5 px-3 text-xs">
                        <i class="las la-magic"></i> Load Modern Lottery Example
                    </button>
                    <button type="button" id="preset-q4-btn" class="btn-outline py-1.5 px-3 text-xs">
                        <i class="las la-layer-group"></i> Load Q4 Services Example
                    </button>
                    <button type="button" id="preset-current-btn" class="btn-secondary py-1.5 px-3 text-xs">
                        <i class="las la-sync"></i> Load Current Menu as Text
                    </button>
                </div>

                <form method="POST" action="{{ route('ussd.menus.import-text', $menu) }}" class="space-y-4">
                    @csrf
                    <div>
                        <textarea name="menu_text" id="menu-text-outline" rows="10" required
                            class="w-full rounded-2xl border border-n30 bg-primary/5 px-4 py-3 font-mono text-xs leading-relaxed"
                            placeholder="What would you like to do?&#10;  1. Play Games&#10;    1. Keno 20/80&#10;      1. Select Balls [API: Please Select Balls]&#10;  2. Contact Us [END: Support: support@q4iltd.com]">{{ old('menu_text', $menuTextOutline) }}</textarea>
                    </div>
                    <div class="grid grid-cols-12 gap-3 items-end">
                        <div class="col-span-12 md:col-span-4">
                            <label class="block text-xs font-medium mb-1">Import Mode</label>
                            <select name="mode" id="import-mode" class="w-full rounded-2xl border border-n30 bg-primary/5 px-3 py-2 text-xs">
                                <option value="replace">Replace Entire Menu</option>
                                <option value="append">Append Under Parent Step</option>
                            </select>
                        </div>
                        <div class="col-span-12 md:col-span-4">
                            <label class="block text-xs font-medium mb-1">Default Leaf Action</label>
                            <select name="default_leaf_type" class="w-full rounded-2xl border border-n30 bg-primary/5 px-3 py-2 text-xs">
                                <option value="API">API (Forward / Dynamic)</option>
                                <option value="END">END (Terminate Message)</option>
                            </select>
                        </div>
                        <div class="col-span-12 md:col-span-4">
                            <button type="submit" class="btn-primary w-full justify-center py-2.5 text-xs"
                                onclick="return document.getElementById('import-mode').value !== 'replace' || confirm('Replace existing steps in this menu with the parsed text outline?')">
                                <i class="las la-bolt"></i> Build Menu from Text
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Add Single Step -->
            <div class="box border border-n30">
                <h4 class="h4 bb-dashed pb-4 mb-4">Add Single Menu Step</h4>
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
                    <p class="text-sm text-n100 py-4">No steps in this menu yet. Use Quick Text-to-Menu Builder or add a top-level ROOT item above.</p>
                @endif
            </div>
        </div>

        <!-- Live USSD Phone Simulator & API Node Tester -->
        <div class="col-span-12 xl:col-span-4 space-y-6">
            <div class="box border border-n30 sticky top-24 space-y-6">
                <div>
                    <h4 class="h4 bb-dashed pb-4 mb-4">Live USSD Simulator</h4>
                    <p class="text-xs text-n100 mb-4">Test your USSD flow in sandbox mode without deducting tokens. Use <strong>0</strong> to go back.</p>

                    <div class="rounded-3xl border-4 border-[#0B3A75] bg-[#061022] p-4 text-white shadow-lg">
                        <div class="flex items-center justify-between text-xs text-blue-200 mb-3">
                            <span>{{ $menu->client?->ussd_code ?: '*384*23#' }}</span>
                            <span id="sim-badge" class="rounded bg-[#0878F8]/25 px-2 py-0.5 text-[#22C7F8]">IDLE</span>
                        </div>
                        <pre id="sim-screen" class="min-h-[160px] whitespace-pre-wrap font-mono text-sm leading-relaxed text-white">Press "Dial Code" to start a USSD session.</pre>
                        <div class="mt-4 flex gap-2">
                            <input type="text" id="sim-input" placeholder="Option (e.g. 1)" disabled
                                class="w-full rounded-xl bg-white/10 border border-white/20 px-3 py-2 text-sm text-white placeholder-slate-300" />
                            <button type="button" id="sim-send-btn" disabled class="btn-primary py-2 px-4 text-xs">Send</button>
                        </div>
                        <div class="mt-3 flex gap-2">
                            <button type="button" id="sim-dial-btn" class="btn-secondary w-full justify-center py-2 text-xs">
                                <i class="las la-phone"></i> Dial Code
                            </button>
                        </div>
                    </div>
                </div>

                <!-- External API Callback Tester -->
                <div class="border-t border-n30 pt-5">
                    <h5 class="font-bold text-sm mb-2">External API Node Tester</h5>
                    <p class="text-xs text-n100 mb-3">Verify response time &amp; USSD format of your external API webhook.</p>
                    <div class="space-y-2.5">
                        <input type="url" id="api-test-url"
                            value="{{ config('services.ussd.modern_lottery_url') }}"
                            placeholder="https://your-app.com/ussd/callback"
                            class="w-full rounded-2xl border border-n30 bg-primary/5 px-3 py-2 text-xs" />
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" id="api-test-text" value="1*1" placeholder="Input text (e.g. 1*1)"
                                class="w-full rounded-2xl border border-n30 bg-primary/5 px-3 py-2 text-xs" />
                            <input type="text" id="api-test-phone" value="+2348000000000" placeholder="Phone"
                                class="w-full rounded-2xl border border-n30 bg-primary/5 px-3 py-2 text-xs" />
                        </div>
                        <button type="button" id="api-test-btn" class="btn-outline w-full justify-center py-2 text-xs">
                            <i class="las la-vial"></i> Test API Callback
                        </button>
                        <pre id="api-test-result" class="hidden rounded-xl bg-[#0B3A75] text-white p-3 text-[11px] font-mono whitespace-pre-wrap"></pre>
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
        const apiTestUrl = @json(route('ussd.menus.test-api', $menu));
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

        // Text-to-Menu Presets & Live Line Counter
        const outlineEl = document.getElementById('menu-text-outline');
        const countBadge = document.getElementById('text-parse-badge');
        const currentOutline = @json($menuTextOutline);

        const modernLotteryPreset = [
            'What would you like to do?',
            '  1. Play Games',
            '    1. Keno 20/80 (AWOOF)',
            '      1. Select Balls [API: Please Select Balls]',
            '      2. More Games',
            '        1. SUM BIG [API: You Selected SUM BIG]',
            '        2. SUM SMALL [API: You Selected SUM SMALL]',
            '        3. SUM ODD [API: You selected SUM ODD]',
            '        4. SUM EVEN [API: You Selected SUM EVEN]',
            '        5. GOLD (9.2 ODDS) [API: You selected GOLD (9.2 ODDS)]',
            '    2. Modern BILLIONAIRE',
            '      1. Powerball',
            '        1. PERM1 [API: You Selected PERM1]',
            '        2. PERM2 [API: You Selected PERM2]',
            '        3. PERM3 [API: You Selected PERM3]',
            '        4. 1BANKER [API: You Selected 1BANKER]',
            '  2. Check results',
            '    1. Powerball Results [API: Powerball Results]',
            '    2. Keno Results [API: Keno Results]',
            '  3. Recharge [API: Recharge]',
            '  4. Withdraw [API: Withdrawal]',
            '  5. Check Balance [API: Wallets]',
            '  6. Contact Us [END: Please contact support@q4iltd.com]'
        ].join('\n');

        const q4ServicesPreset = [
            'Welcome to Q4 Services',
            '  1. Check Account Balance [API: Balance Inquiry]',
            '  2. Buy Airtime & Data',
            '    1. MTN [API: Enter Amount]',
            '    2. Airtel [API: Enter Amount]',
            '    3. Glo [API: Enter Amount]',
            '    4. T2 (9mobile) [API: Enter Amount]',
            '  3. Social Betting & Wallet [API: Q4 Betting]',
            '  4. Support [END: Email support@q4iltd.com or call +234 904 593 6806]'
        ].join('\n');

        function updateOutlineCount() {
            if (!outlineEl || !countBadge) return;
            const lines = outlineEl.value.split(/\r?\n/).filter(l => l.trim() && !l.trim().startsWith('#'));
            countBadge.textContent = `${lines.length} screen/step line(s)`;
        }

        outlineEl && outlineEl.addEventListener('input', updateOutlineCount);
        updateOutlineCount();

        document.getElementById('preset-lottery-btn')?.addEventListener('click', () => {
            outlineEl.value = modernLotteryPreset;
            updateOutlineCount();
        });
        document.getElementById('preset-q4-btn')?.addEventListener('click', () => {
            outlineEl.value = q4ServicesPreset;
            updateOutlineCount();
        });
        document.getElementById('preset-current-btn')?.addEventListener('click', () => {
            outlineEl.value = currentOutline;
            updateOutlineCount();
        });

        // External API Tester
        const apiBtn = document.getElementById('api-test-btn');
        const apiOut = document.getElementById('api-test-result');
        apiBtn?.addEventListener('click', async () => {
            const url = document.getElementById('api-test-url').value.trim();
            const text = document.getElementById('api-test-text').value;
            const phone = document.getElementById('api-test-phone').value;
            if (!url) return;

            apiOut.classList.remove('hidden');
            apiOut.textContent = 'Testing endpoint...';

            try {
                const res = await fetch(apiTestUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ url, text, phoneNumber: phone })
                });
                const data = await res.json();
                apiOut.textContent = `HTTP ${data.status_code ?? res.status} (${data.latency_ms ?? 0}ms)\n${data.reply ?? data.message ?? ''}`;
            } catch (err) {
                apiOut.textContent = 'Test request failed.';
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
