@extends('authentication.layout')
@section('title', 'Reset Password — ' . config('app.name'))

@section('header-action')
    <a href="{{ route('login') }}"
        class="rounded-full bg-[#0878F8] px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-[#0B3A75] transition-colors">
        Sign In
    </a>
@endsection

@section('auth-content')
<div class="w-full max-w-[490px] auth-frosted-panel rounded-3xl p-7 sm:p-9">
    <div class="flex items-center gap-3 mb-5">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#6D5EF7]/15 text-[#6D5EF7] border border-[#6D5EF7]/25">
            <i class="las la-unlock-alt text-2xl"></i>
        </div>
        <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-[#6D5EF7]">Password Reset</span>
            <h1 class="text-2xl font-extrabold text-[#0B3A75] dark:text-white leading-tight">Choose New Password</h1>
        </div>
    </div>

    <p class="text-sm text-slate-600 dark:text-slate-300 mb-6 pb-5 border-b border-[#D1D5DB]/60 dark:border-white/10">
        Create a strong password of at least 8 characters to secure your {{ config('app.name') }} account.
    </p>

    @include('partials._alerts')

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}" />

        <div>
            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-[#0B3A75] dark:text-slate-200 mb-2">
                New Password
            </label>
            <div class="relative">
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-[#0878F8]">
                    <i class="las la-lock text-lg"></i>
                </span>
                <input type="password" name="password" id="password" required autofocus
                    class="auth-glass-input w-full rounded-2xl pl-11 pr-11 py-3 text-sm"
                    placeholder="Min. 8 characters" />
                <button type="button" onclick="toggleAuthPassword('password', this)"
                    aria-label="Toggle password visibility"
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-[#0878F8] cursor-pointer">
                    <i class="las la-eye text-lg"></i>
                </button>
            </div>
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-[#0B3A75] dark:text-slate-200 mb-2">
                Confirm New Password
            </label>
            <div class="relative">
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-[#0878F8]">
                    <i class="las la-check-circle text-lg"></i>
                </span>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                    class="auth-glass-input w-full rounded-2xl pl-11 pr-11 py-3 text-sm"
                    placeholder="Repeat new password" />
                <button type="button" onclick="toggleAuthPassword('password_confirmation', this)"
                    aria-label="Toggle confirmation password visibility"
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-[#0878F8] cursor-pointer">
                    <i class="las la-eye text-lg"></i>
                </button>
            </div>
        </div>

        <button type="submit"
            class="mt-2 w-full rounded-2xl bg-gradient-to-r from-[#0878F8] to-[#0B3A75] px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-[#0878F8]/30 hover:opacity-95 transition-all cursor-pointer">
            Update Password <i class="las la-check ml-1"></i>
        </button>

        <div class="pt-2 text-center text-xs">
            <a href="{{ route('login') }}" class="font-semibold text-[#0878F8] hover:underline">&larr; Return to Sign In</a>
        </div>
    </form>
</div>
@endsection
