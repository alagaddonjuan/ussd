@extends('authentication.layout')
@section('title', 'Sign In — ' . config('app.name'))

@section('header-action')
    <a href="{{ route('register') }}"
        class="rounded-full bg-[#0878F8] px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-[#0B3A75] transition-colors">
        Create Account
    </a>
@endsection

@section('auth-content')
<div class="w-full max-w-[1040px] auth-frosted-panel rounded-3xl overflow-hidden grid grid-cols-12">
    @include('authentication._brand-panel', [
        'badge' => 'Q4I Client & Admin Console',
        'heading' => 'Control USSD Menus, SMS & Wallet Billing in Real Time.',
        'subtext' => 'Access your visual & text USSD builder, session duration logs, developer API keys, and Paystack top-ups from one unified glass workspace.',
    ])

    <div class="col-span-12 lg:col-span-7 p-6 sm:p-10 lg:p-12 flex flex-col justify-center">
        <div class="mb-6">
            <span class="inline-block rounded-full bg-[#0878F8]/10 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-[#0878F8] mb-3">
                Secure Portal Login
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0B3A75] dark:text-white">Welcome Back</h1>
            <p class="mt-1.5 text-sm text-slate-600 dark:text-slate-300">
                Sign in to manage your USSD menus, messaging, and wallet balance.
            </p>
        </div>

        @include('partials._alerts')

        <form method="POST" action="{{ route('login.submit') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-[#0B3A75] dark:text-slate-200 mb-2">
                    Email Address
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

            <div>
                <div class="flex items-center justify-between mb-2">
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-[#0B3A75] dark:text-slate-200">
                        Password
                    </label>
                    <a href="{{ route('password.request') }}" class="text-xs font-semibold text-[#0878F8] hover:underline">
                        Forgot Password?
                    </a>
                </div>
                <div class="relative">
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-[#0878F8]">
                        <i class="las la-lock text-lg"></i>
                    </span>
                    <input type="password" name="password" id="password" required
                        class="auth-glass-input w-full rounded-2xl pl-11 pr-11 py-3 text-sm"
                        placeholder="Enter your password" />
                    <button type="button" onclick="toggleAuthPassword('password', this)"
                        aria-label="Toggle password visibility"
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-[#0878F8] cursor-pointer">
                        <i class="las la-eye text-lg"></i>
                    </button>
                </div>
            </div>

            <button type="submit"
                class="w-full rounded-2xl bg-gradient-to-r from-[#0878F8] to-[#0B3A75] px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-[#0878F8]/30 hover:opacity-95 transition-all cursor-pointer">
                Sign In to Console <i class="las la-arrow-right ml-1"></i>
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600 dark:text-slate-300">
            Don&apos;t have an account yet?
            <a class="font-bold text-[#0878F8] hover:underline" href="{{ route('register') }}">Create Client Account</a>
        </p>
    </div>
</div>
@endsection
