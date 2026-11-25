@php
    $authUser = auth()->user();
@endphp
<nav class="navbar-top topbarfull z-20 gap-3 bg-n0 py-3 shadow-sm duration-300 border-b border-n0" id="topbar">
    <div class="topbar-inner flex items-center justify-between">
        <div class="flex grow items-center gap-4 xxl:gap-6">
            <a href="{{ $authUser?->isAdmin() ? route('admin.dashboard') : route('dashboard') }}" class="topbar-logo hidden shrink-0">
                <img width="174" height="38" src="{{ asset('assets/images/logo-with-text.png') }}" alt="logo"
                    class="logo-full2 hidden lg:block" />
            </a>
            <button type="button" class="flex items-center rounded-s-2xl bg-primary px-0.5 py-3 text-xl text-white"
                id="sidebar-toggle-btn" aria-label="Toggle sidebar">
                <i class="las la-angle-left text-lg"></i>
            </button>

            <div class="topnav-layout">
                <div id="layout-btn"
                    class="flex w-full cursor-pointer items-center justify-between gap-2 rounded-[30px] border border-n30 bg-primary/5 px-4 py-1 lg:py-1.5 xxl:px-6 xxl:py-2">
                    <span class="flex select-none items-center gap-2">
                        <i class="las la-border-all text-3xl text-primary"></i>
                        <span id="selected-layout" class="capitalize">Vertical</span>
                    </span>
                    <i id="drop-arrow" class="las la-angle-down shrink-0 text-lg duration-300"></i>
                </div>
                <ul id="layout"
                    class="hide absolute left-0 top-full z-20 w-full origin-top rounded-lg bg-n0 p-2 shadow-[0px_6px_30px_0px_rgba(0,0,0,0.08)] duration-300">
                    <li data-layout="vertical" class="active block cursor-pointer select-none rounded-md p-2 duration-300 hover:text-primary">Vertical</li>
                    <li data-layout="two-column" class="block cursor-pointer select-none rounded-md p-2 duration-300 hover:text-primary">Two-Column</li>
                    <li data-layout="hovered" class="block cursor-pointer select-none rounded-md p-2 duration-300 hover:text-primary">Hovered</li>
                    <li data-layout="horizontal" class="block cursor-pointer select-none rounded-md p-2 duration-300 hover:text-primary">Horizontal</li>
                    <li data-layout="detached" class="block cursor-pointer select-none rounded-md p-2 duration-300 hover:text-primary">Detached</li>
                </ul>
            </div>

            <div class="hidden md:flex items-center gap-2 rounded-[30px] border border-n30 bg-primary/5 px-4 py-2 text-sm">
                <span class="text-n100">USSD Code:</span>
                <span class="font-semibold text-primary">{{ $authUser?->ussd_code ?: 'Unassigned' }}</span>
                <span class="mx-2 text-n40">|</span>
                <span class="text-n100">Balance:</span>
                <span class="font-semibold">₦{{ number_format((float) ($authUser?->token_balance ?? 0), 2) }}</span>
            </div>
        </div>

        <div class="flex items-center gap-3 sm:gap-4">
            <div class="relative lg:hidden">
                <button type="button" id="mobile-search-btn"
                    class="flex h-10 w-10 cursor-pointer select-none items-center justify-center gap-2 rounded-full border border-n30 bg-primary/5 md:h-12 md:w-12"
                    aria-label="Quick balance info">
                    <i class="las la-wallet"></i>
                </button>
                <div id="mobile-search"
                    class="hide invisible absolute -left-8 top-full z-20 flex min-w-max max-w-[260px] origin-[20%_20%] flex-col gap-1 rounded-md bg-n0 p-3 opacity-0 shadow-[0px_6px_30px_0px_rgba(0,0,0,0.08)] duration-300">
                    <span class="text-xs text-n100">Balance</span>
                    <span class="font-semibold text-primary">₦{{ number_format((float) ($authUser?->token_balance ?? 0), 2) }}</span>
                    <span class="text-xs text-n100">USSD: {{ $authUser?->ussd_code ?: 'Unassigned' }}</span>
                </div>
            </div>

            <button type="button" id="darkModeToggle" aria-label="dark mode switch"
                class="h-10 w-10 shrink-0 rounded-full border border-n30 bg-primary/5 md:h-12 md:w-12">
                <i class="las la-sun text-2xl dark:hidden!"></i>
                <span class="hidden! dark:block!">
                    <i class="las la-moon text-2xl"></i>
                </span>
            </button>

            <div class="relative shrink-0">
                <div id="profile-btn" class="flex items-center gap-2 cursor-pointer">
                    <div class="flex h-10 w-10 md:h-12 md:w-12 items-center justify-center rounded-full bg-primary/15 text-primary font-semibold">
                        {{ strtoupper(substr($authUser?->name ?? 'Q', 0, 1)) }}
                    </div>
                </div>
                <div id="profile"
                    class="hide absolute top-full z-20 rounded-md bg-n0 shadow-[0px_6px_30px_0px_rgba(0,0,0,0.08)] duration-300 ltr:right-0 ltr:origin-top-right rtl:left-0 rtl:origin-top-left">
                    <div class="flex flex-col items-center border-b border-n40 p-3 text-center lg:p-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary text-white text-lg font-bold">
                            {{ strtoupper(substr($authUser?->name ?? 'Q', 0, 1)) }}
                        </div>
                        <h6 class="h6 mt-2">{{ $authUser?->name }}</h6>
                        <span class="text-xs text-n100">{{ $authUser?->email }}</span>
                        @if ($authUser?->isAdmin())
                            <span class="mt-2 inline-block rounded-full bg-primary/10 px-3 py-0.5 text-xs font-medium text-primary">Administrator</span>
                        @endif
                    </div>
                    <ul class="flex w-[250px] flex-col p-4">
                        <li>
                            <a href="{{ route('settings.profile') }}"
                                class="flex items-center gap-2 rounded-md p-2 duration-300 hover:bg-primary hover:text-n0">
                                <i class="las la-user text-xl"></i>
                                <span>Profile</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('settings.security') }}"
                                class="flex items-center gap-2 rounded-md p-2 duration-300 hover:bg-primary hover:text-n0">
                                <i class="las la-shield-alt text-xl"></i>
                                <span>Security &amp; 2FA</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('billing.index') }}"
                                class="flex items-center gap-2 rounded-md p-2 duration-300 hover:bg-primary hover:text-n0">
                                <i class="las la-wallet text-xl"></i>
                                <span>Wallet &amp; Billing</span>
                            </a>
                        </li>
                        <li class="mt-2 border-t border-n30 pt-2">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="flex w-full items-center gap-2 rounded-md p-2 text-left text-error duration-300 hover:bg-error hover:text-white">
                                    <i class="las la-sign-out-alt text-xl"></i>
                                    <span>Sign Out</span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>
