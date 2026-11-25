@extends('layout.main')
@section('title', 'Security & 2FA — ' . config('app.name'))

@section('content')
<div class="main-inner">
    <div class="mb-6 lg:mb-8">
        <h2 class="h2">Security &amp; Two-Factor Authentication</h2>
    </div>

    <div class="box max-w-2xl border border-n30">
        <div class="bb-dashed pb-4 mb-6 flex items-center justify-between">
            <div>
                <h4 class="h4">Authenticator App (TOTP)</h4>
                <p class="text-sm text-n100 mt-1">Protect your account with Google Authenticator or Authy.</p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $client->is_2fa_enabled ? 'bg-primary/10 text-primary' : 'bg-warning/10 text-warning' }}">
                {{ $client->is_2fa_enabled ? 'Enabled' : 'Disabled' }}
            </span>
        </div>

        @if (! $client->is_2fa_enabled)
            @if ($qrSvg && $pendingSecret)
                <div class="space-y-4">
                    <div class="inline-block rounded-2xl border border-n30 bg-white p-4">
                        {!! $qrSvg !!}
                    </div>
                    <p class="text-xs text-n100">Manual Setup Key: <code class="font-semibold text-primary">{{ $pendingSecret }}</code></p>

                    <form method="POST" action="{{ route('settings.2fa.enable') }}" class="flex flex-wrap gap-3 items-end">
                        @csrf
                        <div class="flex-1 min-w-[200px]">
                            <label for="code" class="block font-medium mb-2 text-sm">Enter 6-Digit Code to Confirm</label>
                            <input type="text" name="code" id="code" required
                                class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-3 text-sm" placeholder="000000" />
                        </div>
                        <button type="submit" class="btn-primary">Verify &amp; Enable 2FA</button>
                    </form>
                </div>
            @else
                <form method="POST" action="{{ route('settings.2fa.generate') }}">
                    @csrf
                    <button type="submit" class="btn-primary">
                        <i class="las la-qrcode"></i>
                        <span>Generate 2FA QR Code</span>
                    </button>
                </form>
            @endif
        @else
            <form method="POST" action="{{ route('settings.2fa.disable') }}" class="flex flex-wrap gap-3 items-end">
                @csrf
                <div class="flex-1 min-w-[200px]">
                    <label for="disable_code" class="block font-medium mb-2 text-sm">Enter Current 6-Digit Code to Disable 2FA</label>
                    <input type="text" name="code" id="disable_code" required
                        class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-3 text-sm" placeholder="000000" />
                </div>
                <button type="submit" class="btn-error">Disable 2FA</button>
            </form>
        @endif
    </div>
</div>
@endsection
