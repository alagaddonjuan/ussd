@extends('authentication.layout')
@section('title', 'Forgot Password — ' . config('app.name'))

@section('header-action')
    <a href="{{ route('login') }}"
        class="rounded-full bg-[#0878F8] px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-[#0B3A75] transition-colors">
        Sign In
    </a>
@endsection

@section('auth-content')
<div class="w-full max-w-[490px] auth-frosted-panel rounded-3xl p-7 sm:p-9">
    <div class="flex items-center gap-3 mb-5">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#0878F8]/15 text-[#0878F8] border border-[#0878F8]/20">
            <i class="las la-key text-2xl"></i>
        </div>
        <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-[#0878F8]">Account Recovery</span>
            <h1 class="text-2xl font-extrabold text-[#0B3A75] dark:text-white leading-tight">Forgot Password?</h1>
        </div>
    </div>

    <p class="text-sm text-slate-600 dark:text-slate-300 mb-6 pb-5 border-b border-[#D1D5DB]/60 dark:border-white/10">
        Enter your registered email address and we will send you a secure 60-minute password reset link.
    </p>

    @include('partials._alerts')

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="block text-xs font-bold uppercase tracking-wider text-[#0B3A75] dark:text-slate-200 mb-2">
                Registered Email Address
            </label>
            <div class="relative">
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-[#0878F8]">
                    <i class="las la-envelope text-lg"></i>
                </span>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                    class="auth-glass-input w-full rounded-2xl pl-11 pr-4 py-3 text-sm"
                    placeholder="you@company.com" />
            </div>
        </div>

        <button type="submit"
            class="w-full rounded-2xl bg-gradient-to-r from-[#0878F8] to-[#0B3A75] px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-[#0878F8]/30 hover:opacity-95 transition-all cursor-pointer">
            Send Reset Link <i class="las la-paper-plane ml-1"></i>
        </button>

        <div class="flex items-center justify-between pt-2 text-xs text-slate-600 dark:text-slate-300">
            <a href="{{ route('login') }}" class="font-semibold text-[#0878F8] hover:underline">
                &larr; Back to Sign In
            </a>
            <a href="{{ route('register') }}" class="font-semibold hover:text-[#0878F8]">
                Create Account
            </a>
        </div>
    </form>
</div>
@endsection
