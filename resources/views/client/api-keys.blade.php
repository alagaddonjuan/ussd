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
</div>
@endsection
