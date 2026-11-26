@extends('authentication.layout')
@section('title', '2FA Verification — ' . config('app.name'))

@section('header-action')
    <a href="{{ route('login') }}"
        class="rounded-full border border-[#0B3A75]/25 px-4 py-1.5 text-xs font-semibold text-[#0B3A75] dark:text-[#22C7F8] hover:bg-[#0878F8]/10 transition-colors">
        Switch Account
    </a>
@endsection

@section('auth-content')
<div class="w-full max-w-[490px] auth-frosted-panel rounded-3xl p-7 sm:p-9">
    <div class="flex items-center gap-3 mb-5">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#10B981]/15 text-[#10B981] border border-[#10B981]/25">
            <i class="las la-shield-alt text-2xl"></i>
        </div>
        <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-[#10B981]">Authenticator Check</span>
            <h1 class="text-2xl font-extrabold text-[#0B3A75] dark:text-white leading-tight">Two-Factor Verification</h1>
        </div>
    </div>

    <p class="text-sm text-slate-600 dark:text-slate-300 mb-6 pb-5 border-b border-[#D1D5DB]/60 dark:border-white/10">
        Enter the 6-digit one-time code from your authenticator app to complete login.
    </p>

    @include('partials._alerts')

    <form method="POST" action="{{ route('2fa.verify') }}" class="space-y-5">
        @csrf
        <div>
            <label for="code" class="block text-xs font-bold uppercase tracking-wider text-[#0B3A75] dark:text-slate-200 mb-2">
                6-Digit Verification Code
            </label>
            <input type="text" name="code" id="code" inputmode="numeric" maxlength="10" required autofocus
                class="auth-glass-input w-full text-center tracking-[0.45em] font-mono text-2xl font-bold rounded-2xl px-4 py-3.5"
                placeholder="000000" />
        </div>

        <button type="submit"
            class="w-full rounded-2xl bg-gradient-to-r from-[#0878F8] to-[#0B3A75] px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-[#0878F8]/30 hover:opacity-95 transition-all cursor-pointer">
            Verify &amp; Continue <i class="las la-arrow-right ml-1"></i>
        </button>

        <div class="pt-2 text-center text-xs">
            <a href="{{ route('login') }}" class="font-semibold text-[#0878F8] hover:underline">&larr; Back to Login</a>
        </div>
    </form>
</div>
@endsection
