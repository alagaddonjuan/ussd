@php
    $authUser = auth()->user();
@endphp
<aside id="sidebar" class="sidebar bg-n0">
    <div class="sidebar-inner relative">
        <div class="logo-column">
            <div class="logo-container">
                <div class="logo-inner">
                    <a href="{{ $authUser?->isAdmin() ? route('admin.dashboard') : route('dashboard') }}" class="logo-wrapper flex items-center gap-2">
                        <img src="{{ asset('assets/images/logo-with-text.png') }}" width="174" height="38"
                            class="logo-full" alt="Q4I Comms" />
                        <img src="{{ asset('assets/images/logo.png') }}" width="37" height="36" class="logo-icon hidden"
                            alt="Q4I" />
                    </a>
                    <img width="141" height="38" class="logo-text hidden"
                        src="{{ asset('assets/images/logo-text.png') }}" alt="Q4I Comms" />
                    <button type="button" class="sidebar-close-btn xl:hidden" id="sidebar-close-btn" aria-label="Close sidebar">
                        <i class="las la-times"></i>
                    </button>
                </div>
            </div>
            <div class="menu-container pb-28">
                <div class="menu-wrapper">
                    <p class="menu-heading">Client Portal</p>
                    <ul class="menu-ul">
                        <li class="menu-li">
                            <a href="{{ route('dashboard') }}" class="menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i class="las la-home"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="menu-li">
                            <a href="{{ route('ussd.menus.index') }}" class="menu-link {{ request()->routeIs('ussd.*') ? 'active' : '' }}">
                                <i class="las la-sitemap"></i>
                                <span>USSD Menus</span>
                            </a>
                        </li>
                        <li class="menu-li">
                            <a href="{{ route('services.index') }}" class="menu-link {{ request()->routeIs('services.*') ? 'active' : '' }}">
                                <i class="las la-sms"></i>
                                <span>SMS &amp; Airtime</span>
                            </a>
                        </li>
                        <li class="menu-li">
                            <a href="{{ route('billing.index') }}" class="menu-link {{ request()->routeIs('billing.*') ? 'active' : '' }}">
                                <i class="las la-wallet"></i>
                                <span>Billing &amp; Wallet</span>
                            </a>
                        </li>
                        <li class="menu-li">
                            <a href="{{ route('keys.index') }}" class="menu-link {{ request()->routeIs('keys.*') ? 'active' : '' }}">
                                <i class="las la-key"></i>
                                <span>API Keys</span>
                            </a>
                        </li>
                        <li class="menu-li">
                            <a href="{{ route('team.index') }}" class="menu-link {{ request()->routeIs('team.*') ? 'active' : '' }}">
                                <i class="las la-users"></i>
                                <span>Team Members</span>
                            </a>
                        </li>
                        <li class="menu-li">
                            <button type="button" class="menu-btn group bg-n0 {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                                <span class="flex items-center justify-center gap-2">
                                    <span class="menu-icon"><i class="las la-cog"></i></span>
                                    <span class="menu-title font-medium">Settings</span>
                                </span>
                                <span class="plus-minus">
                                    <i class="las la-plus text-xl"></i>
                                    <i class="las la-minus text-xl"></i>
                                </span>
                                <span class="chevron-down hidden"><i class="las la-angle-down text-base"></i></span>
                            </button>
                            <ul class="submenu {{ request()->routeIs('settings.*') ? 'submenu-show' : 'submenu-hide' }}">
                                <li>
                                    <a href="{{ route('settings.profile') }}" class="submenu-link {{ request()->routeIs('settings.profile') ? 'text-primary' : '' }}">
                                        <i class="las la-minus text-xl"></i>
                                        <span>Profile</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('settings.security') }}" class="submenu-link {{ request()->routeIs('settings.security') ? 'text-primary' : '' }}">
                                        <i class="las la-minus text-xl"></i>
                                        <span>Security &amp; 2FA</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>

                    @if ($authUser?->isAdmin())
                        <p class="menu-heading mt-6">Administration</p>
                        <ul class="menu-ul">
                            <li class="menu-li">
                                <a href="{{ route('admin.dashboard') }}" class="menu-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                                    <i class="las la-chart-area"></i>
                                    <span>Admin Overview</span>
                                </a>
                            </li>
                            <li class="menu-li">
                                <a href="{{ route('admin.clients') }}" class="menu-link {{ request()->routeIs('admin.clients') ? 'active' : '' }}">
                                    <i class="las la-user-shield"></i>
                                    <span>Clients &amp; Wallets</span>
                                </a>
                            </li>
                            <li class="menu-li">
                                <a href="{{ route('admin.pricing-tiers') }}" class="menu-link {{ request()->routeIs('admin.pricing-tiers') ? 'active' : '' }}">
                                    <i class="las la-tags"></i>
                                    <span>Pricing &amp; Telcos</span>
                                </a>
                            </li>
                            <li class="menu-li">
                                <a href="{{ route('admin.ussd-menus') }}" class="menu-link {{ request()->routeIs('admin.ussd-menus') ? 'active' : '' }}">
                                    <i class="las la-project-diagram"></i>
                                    <span>All USSD Menus</span>
                                </a>
                            </li>
                            <li class="menu-li">
                                <a href="{{ route('admin.logs') }}" class="menu-link {{ request()->routeIs('admin.logs') ? 'active' : '' }}">
                                    <i class="las la-list-alt"></i>
                                    <span>System Logs</span>
                                </a>
                            </li>
                            <li class="menu-li">
                                <a href="{{ route('admin.announcements') }}" class="menu-link {{ request()->routeIs('admin.announcements') ? 'active' : '' }}">
                                    <i class="las la-bullhorn"></i>
                                    <span>Broadcast</span>
                                </a>
                            </li>
                        </ul>
                    @endif
                </div>

                <div class="px-4">
                    <div class="balance-part">
                        <p class="border-t-2 border-dashed border-primary/20 py-4 text-xs font-semibold lg:py-6">
                            Wallet Summary
                        </p>
                        <ul class="space-y-2">
                            <li class="flex items-center justify-between rounded-xl bg-primary/5 px-4 py-3">
                                <span class="text-xs text-n100">Tokens</span>
                                <span class="font-semibold text-primary">₦{{ number_format((float) ($authUser?->token_balance ?? 0), 2) }}</span>
                            </li>
                            <li class="flex items-center justify-between rounded-xl bg-secondary/5 px-4 py-3">
                                <span class="text-xs text-n100">USSD</span>
                                <span class="font-semibold">{{ $authUser?->ussd_code ?: 'Unassigned' }}</span>
                            </li>
                            <li>
                                <a href="{{ route('billing.index') }}" class="btn-primary mt-2 flex w-full justify-center text-sm">
                                    <i class="las la-plus-circle"></i>
                                    <span>Fund Wallet</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</aside>
