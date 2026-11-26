@extends('authentication.layout')
@section('title', 'Sign Up — ' . config('app.name'))

@section('header-action')
    <a href="{{ route('login') }}"
        class="rounded-full bg-[#0878F8] px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-[#0B3A75] transition-colors">
        Sign In
    </a>
@endsection

@section('auth-content')
<div class="w-full max-w-[1040px] auth-frosted-panel rounded-3xl overflow-hidden grid grid-cols-12">
    @include('authentication._brand-panel', [
        'badge' => 'Instant API & USSD Onboarding',
        'heading' => 'Launch Enterprise USSD & Messaging in Minutes.',
        'subtext' => 'Register your organization account to receive an instant default API key, Text-to-Menu USSD builder access, Bulk SMS, and Paystack wallet top-ups.',
    ])

    <div class="col-span-12 lg:col-span-7 p-6 sm:p-10 lg:p-12 flex flex-col justify-center">
        <div class="mb-6">
            <span class="inline-block rounded-full bg-[#10B981]/15 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-[#10B981] mb-3">
                New Organization Setup
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0B3A75] dark:text-white">Create Client Account</h1>
            <p class="mt-1.5 text-sm text-slate-600 dark:text-slate-300">
                Get instant access to USSD Menu Builder, Bulk SMS, Airtime &amp; API Keys.
            </p>
        </div>

        @include('partials._alerts')

        <form method="POST" action="{{ route('register.submit') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-xs font-bold uppercase tracking-wider text-[#0B3A75] dark:text-slate-200 mb-2">
                    Company / Account Name
                </label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-[#0878F8]">
                        <i class="las la-building text-lg"></i>
                    </span>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus
                        class="auth-glass-input w-full rounded-2xl pl-11 pr-4 py-3 text-sm"
                        placeholder="Acme Communications Ltd" />
                </div>
            </div>

            <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-[#0B3A75] dark:text-slate-200 mb-2">
                    Work Email Address
                </label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-[#0878F8]">
                        <i class="las la-envelope text-lg"></i>
                    </span>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required
                        class="auth-glass-input w-full rounded-2xl pl-11 pr-4 py-3 text-sm"
                        placeholder="you@company.com" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-[#0B3A75] dark:text-slate-200 mb-2">
                        Password
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-[#0878F8]">
                            <i class="las la-lock text-lg"></i>
                        </span>
                        <input type="password" name="password" id="password" required
                            class="auth-glass-input w-full rounded-2xl pl-11 pr-10 py-3 text-sm"
                            placeholder="Min. 8 chars" />
                        <button type="button" onclick="toggleAuthPassword('password', this)"
                            aria-label="Toggle password visibility"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-[#0878F8] cursor-pointer">
                            <i class="las la-eye text-base"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-[#0B3A75] dark:text-slate-200 mb-2">
                        Confirm Password
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-[#0878F8]">
                            <i class="las la-check-circle text-lg"></i>
                        </span>
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                            class="auth-glass-input w-full rounded-2xl pl-11 pr-10 py-3 text-sm"
                            placeholder="Repeat password" />
                        <button type="button" onclick="toggleAuthPassword('password_confirmation', this)"
                            aria-label="Toggle password confirmation visibility"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-[#0878F8] cursor-pointer">
                            <i class="las la-eye text-base"></i>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit"
                class="mt-2 w-full rounded-2xl bg-gradient-to-r from-[#0878F8] to-[#0B3A75] px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-[#0878F8]/30 hover:opacity-95 transition-all cursor-pointer">
                Create Account &amp; Get API Key <i class="las la-arrow-right ml-1"></i>
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600 dark:text-slate-300">
            Already have an account?
            <a class="font-bold text-[#0878F8] hover:underline" href="{{ route('login') }}">Sign In</a>
        </p>
    </div>
</div>
@endsection
