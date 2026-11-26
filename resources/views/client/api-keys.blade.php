@extends('layout.main')
@section('title', 'API Keys — ' . config('app.name'))

@section('content')
<div class="main-inner">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 lg:mb-8">
        <div>
            <h2 class="h2">API Keys &amp; IP Whitelist</h2>
            <p class="text-sm text-n100 mt-1">Manage API credentials and allowed server IPs for your account.</p>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6">
        <div class="box col-span-12 lg:col-span-4 border border-n30">
            <h4 class="h4 bb-dashed pb-4 mb-4">Create New API Key</h4>
            <form method="POST" action="{{ route('keys.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="key_name" class="block font-medium mb-2">Key Name</label>
                    <input type="text" name="key_name" id="key_name" required
                        class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-3 text-sm"
                        placeholder="Production Server" />
                </div>
                <div>
                    <label for="whitelisted_ips" class="block font-medium mb-2">Whitelisted IPs (comma-separated, optional)</label>
                    <input type="text" name="whitelisted_ips" id="whitelisted_ips"
                        class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-3 text-sm"
                        placeholder="192.168.1.10, 102.89.10.2" />
                </div>
                <button type="submit" class="btn-primary w-full justify-center">Generate Key</button>
            </form>
        </div>

        <div class="box col-span-12 lg:col-span-8 border border-n30">
            <h4 class="h4 bb-dashed pb-4 mb-4">Active API Keys</h4>
            <div class="space-y-4">
                @forelse ($apiKeys as $key)
                    <div class="rounded-xl border border-n30 bg-primary/5 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                            <div>
                                <p class="font-semibold">{{ $key->key_name }}</p>
                                <code class="text-xs text-primary break-all">{{ $key->api_key }}</code>
                            </div>
                            <form method="POST" action="{{ route('keys.destroy', $key) }}" onsubmit="return confirm('Revoke this API key?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-error py-1.5 px-4 text-xs">Revoke</button>
                            </form>
                        </div>
                        <form method="POST" action="{{ route('keys.update', $key) }}" class="flex flex-wrap gap-2">
                            @csrf
                            @method('PUT')
                            <input type="text" name="whitelisted_ips" value="{{ $key->whitelisted_ips }}"
                                class="flex-1 rounded-3xl border border-n30 bg-n0 px-4 py-2 text-xs"
                                placeholder="Allowed IPs (e.g., 127.0.0.1)" />
                            <button type="submit" class="btn-outline py-1.5 px-4 text-xs">Save IPs</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-n100 py-4">No API keys generated yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Developer Quickstart & Code Snippets -->
    <div class="box border border-n30 mt-6">
        <div class="bb-dashed mb-4 pb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h4 class="h4">Developer Integration Snippets</h4>
                <p class="text-xs text-n100 mt-1">Connect your application backend or external USSD endpoint to {{ config('app.name') }}.</p>
            </div>
            <div class="flex flex-wrap gap-2" id="api-doc-tabs">
                <button type="button" data-tab="curl" class="btn-primary py-1.5 px-3 text-xs doc-tab-btn">cURL</button>
                <button type="button" data-tab="php" class="btn-outline py-1.5 px-3 text-xs doc-tab-btn">PHP</button>
                <button type="button" data-tab="node" class="btn-outline py-1.5 px-3 text-xs doc-tab-btn">Node.js</button>
                <button type="button" data-tab="python" class="btn-outline py-1.5 px-3 text-xs doc-tab-btn">Python</button>
            </div>
        </div>

        <pre id="doc-code-curl" class="doc-pane rounded-2xl bg-[#0B3A75] text-slate-100 p-4 text-xs font-mono overflow-x-auto"># 1. USSD Callback Test
curl -X POST {{ url('/ussd') }} \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "sessionId=SESSION_001&amp;serviceCode={{ $client->ussd_code ?: '*384*23#' }}&amp;phoneNumber=+2348000000000&amp;text="

# 2. Programmatic Bulk SMS API
curl -X POST {{ url('/api/v1/sms/send') }} \
  -H "X-API-Key: {{ $apiKeys->first()?->api_key ?? 'YOUR_API_KEY' }}" \
  -H "Content-Type: application/json" \
  -d '{"to":"+2348012345678","message":"Your Q4 OTP is 492018"}'

# 3. Programmatic Airtime API
curl -X POST {{ url('/api/v1/airtime/vend') }} \
  -H "X-API-Key: {{ $apiKeys->first()?->api_key ?? 'YOUR_API_KEY' }}" \
  -H "Content-Type: application/json" \
  -d '{"phone_number":"+2348012345678","amount":100,"network":"MTN"}'</pre>

        <pre id="doc-code-php" class="doc-pane hidden rounded-2xl bg-[#0B3A75] text-slate-100 p-4 text-xs font-mono overflow-x-auto">&lt;?php
// External USSD Webhook Handler (set inside USSD Menu Builder API node)
header('Content-Type: text/plain');
$sessionId   = $_POST['sessionId'] ?? '';
$phoneNumber = $_POST['phoneNumber'] ?? '';
$text        = $_POST['text'] ?? '';

echo $text === ''
    ? "CON Welcome to Q4 Services\n1. Check Wallet\n2. Support"
    : "END Request completed for {$phoneNumber}.";</pre>

        <pre id="doc-code-node" class="doc-pane hidden rounded-2xl bg-[#0B3A75] text-slate-100 p-4 text-xs font-mono overflow-x-auto">app.post('/ussd/callback', (req, res) =&gt; {
  const { sessionId, serviceCode, phoneNumber, text } = req.body;
  res.type('text/plain');
  if (!text) return res.send('CON Welcome to Q4 Services\n1. Continue\n2. Support');
  return res.send(`END Processed ${text} for ${phoneNumber}`);
});</pre>

        <pre id="doc-code-python" class="doc-pane hidden rounded-2xl bg-[#0B3A75] text-slate-100 p-4 text-xs font-mono overflow-x-auto">import requests

resp = requests.post("{{ url('/ussd') }}", data={
    "sessionId": "PY_TEST_001",
    "serviceCode": "{{ $client->ussd_code ?: '*384*23#' }}",
    "phoneNumber": "+2348000000000",
    "text": "1"
}, timeout=10)
print(resp.text)</pre>
    </div>
</div>
@endsection

@push('page-js')
<script>
    document.querySelectorAll('.doc-tab-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.doc-tab-btn').forEach((b) => {
                b.classList.remove('btn-primary');
                b.classList.add('btn-outline');
            });
            btn.classList.remove('btn-outline');
            btn.classList.add('btn-primary');

            const target = btn.getAttribute('data-tab');
            document.querySelectorAll('.doc-pane').forEach((p) => p.classList.add('hidden'));
            document.getElementById('doc-code-' + target)?.classList.remove('hidden');
        });
    });
</script>
@endpush
