<!DOCTYPE html>
<html dir="ltr" lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon" />
    <meta name="description"
        content="Q4 International — Enterprise USSD menus, Bulk SMS, and Airtime disbursement infrastructure across MTN, Airtel, Glo, and 9mobile." />
    @vite(['resources/css/app.css'])
    <title>Q4 International — Enterprise USSD, SMS &amp; Airtime Platform</title>
</head>

<body class="antialiased text-slate-800 dark:text-slate-100">
    <!-- Ambient Decorative Orbs -->
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -top-28 -left-24 h-96 w-96 rounded-full bg-[#19B896]/25 blur-3xl"></div>
        <div class="absolute top-1/3 -right-24 h-[28rem] w-[28rem] rounded-full bg-[#4371e9]/15 blur-3xl"></div>
        <div class="absolute -bottom-32 left-1/3 h-96 w-96 rounded-full bg-[#0D5A51]/15 blur-3xl"></div>
    </div>

    <!-- Top Glass Navigation -->
    <header class="sticky top-3 z-40 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div
            class="glass-card flex items-center justify-between rounded-full px-4 py-3 sm:px-6 transition-all">
            <a href="{{ route('home') }}" class="flex items-center gap-3 shrink-0">
                <img src="{{ asset('assets/images/q4-logo.png') }}" alt="Q4 International"
                    class="h-10 w-auto object-contain dark:hidden" />
                <img src="{{ asset('assets/images/q4-logo-white.png') }}" alt="Q4 International"
                    class="hidden h-10 w-auto object-contain dark:block" />
            </a>

            <nav class="hidden md:flex items-center gap-7 text-sm font-medium text-slate-700 dark:text-slate-200">
                <a href="#services" class="hover:text-[#127D66] transition-colors">Services</a>
                <a href="#pricing" class="hover:text-[#127D66] transition-colors">Pricing Plans</a>
                <a href="#why-q4" class="hover:text-[#127D66] transition-colors">Why Q4</a>
                <a href="#faq" class="hover:text-[#127D66] transition-colors">FAQ</a>
            </nav>

            <div class="flex items-center gap-2 sm:gap-3">
                @auth
                    <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}"
                        class="inline-flex items-center gap-2 rounded-full bg-[#0D5A51] px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-[#0D5A51]/25 hover:bg-[#127D66] transition-all">
                        <i class="las la-tachometer-alt text-lg"></i>
                        <span>Go to Dashboard</span>
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="hidden sm:inline-flex items-center rounded-full border border-[#0D5A51]/30 px-4 py-2 text-sm font-semibold text-[#0D5A51] dark:text-emerald-300 dark:border-emerald-400/30 hover:bg-[#0D5A51]/10 transition-all">
                        Sign In
                    </a>
                    <a href="{{ route('register') }}"
                        class="inline-flex items-center gap-2 rounded-full bg-[#0D5A51] px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-[#0D5A51]/25 hover:bg-[#127D66] transition-all">
                        <span>Get Started</span>
                        <i class="las la-arrow-right text-base"></i>
                    </a>
                @endauth

                <button type="button" id="landing-menu-btn"
                    class="md:hidden inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-300/60 dark:border-white/20 text-slate-700 dark:text-slate-200"
                    aria-label="Toggle Menu">
                    <i class="las la-bars text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu Dropdown -->
        <div id="landing-mobile-menu"
            class="glass-card mt-2 hidden flex-col gap-2 rounded-2xl p-4 md:hidden">
            <a href="#services" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-emerald-500/10">Services</a>
            <a href="#pricing" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-emerald-500/10">Pricing Plans</a>
            <a href="#why-q4" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-emerald-500/10">Why Q4</a>
            <a href="#faq" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-emerald-500/10">FAQ</a>
            @guest
                <a href="{{ route('login') }}"
                    class="mt-1 rounded-xl border border-[#0D5A51]/30 px-4 py-2 text-center text-sm font-semibold text-[#0D5A51]">
                    Sign In
                </a>
            @endguest
        </div>
    </header>

    <!-- Hero Section -->
    <section class="mx-auto max-w-7xl px-4 pt-12 pb-16 sm:px-6 lg:px-8 lg:pt-16">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            <div class="lg:col-span-7 space-y-6">
                <div
                    class="glass-pill inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-[#0D5A51] dark:text-emerald-300">
                    <span class="inline-block h-2 w-2 rounded-full bg-[#19B896]"></span>
                    <span>Q4 International Comms Gateway</span>
                </div>

                <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-5xl lg:text-6xl leading-[1.1]">
                    Power Seamless Business Communication
                </h1>

                <p class="max-w-2xl text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed">
                    Deploy interactive USSD applications in minutes, deliver mission-critical Bulk SMS, and automate instant Airtime disbursements across MTN, Airtel, Glo, and 9mobile with full Africa&apos;s Talking callback precision.
                </p>

                <div class="flex flex-wrap items-center gap-4 pt-2">
                    @auth
                        <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}"
                            class="inline-flex items-center gap-2 rounded-full bg-[#0D5A51] px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-[#0D5A51]/25 hover:bg-[#127D66] transition-all">
                            <span>Open Portal</span>
                            <i class="las la-arrow-right"></i>
                        </a>
                    @else
                        <a href="{{ route('register') }}"
                            class="inline-flex items-center gap-2 rounded-full bg-[#0D5A51] px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-[#0D5A51]/25 hover:bg-[#127D66] transition-all">
                            <span>Create Client Account</span>
                            <i class="las la-arrow-right"></i>
                        </a>
                        <a href="{{ route('login') }}"
                            class="glass-pill inline-flex items-center gap-2 rounded-full px-6 py-3.5 text-base font-semibold text-slate-800 dark:text-white hover:border-[#19B896] transition-all">
                            <i class="las la-sign-in-alt text-lg text-[#127D66]"></i>
                            <span>Client / Admin Sign In</span>
                        </a>
                    @endauth
                    <a href="#pricing"
                        class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#127D66] dark:text-emerald-300 hover:underline">
                        <span>Compare USSD Pricing Tiers</span>
                        <i class="las la-angle-down"></i>
                    </a>
                </div>

                <!-- Trust KPIs -->
                <div class="grid grid-cols-3 gap-4 pt-4 max-w-xl">
                    <div class="glass-card rounded-2xl p-4">
                        <p class="text-2xl font-bold text-[#0D5A51] dark:text-emerald-300">99.98%</p>
                        <p class="text-xs text-slate-600 dark:text-slate-300 mt-1">Gateway Uptime</p>
                    </div>
                    <div class="glass-card rounded-2xl p-4">
                        <p class="text-2xl font-bold text-[#0D5A51] dark:text-emerald-300">&lt; 250ms</p>
                        <p class="text-xs text-slate-600 dark:text-slate-300 mt-1">USSD Response</p>
                    </div>
                    <div class="glass-card rounded-2xl p-4">
                        <p class="text-2xl font-bold text-[#0D5A51] dark:text-emerald-300">4 Telcos</p>
                        <p class="text-xs text-slate-600 dark:text-slate-300 mt-1">MTN, Airtel, Glo, 9mobile</p>
                    </div>
                </div>
            </div>

            <!-- Hero Frosted Glass Product Preview -->
            <div class="lg:col-span-5">
                <div class="glass-card relative rounded-3xl p-6 sm:p-8 space-y-5">
                    <div class="flex items-center justify-between border-b border-slate-300/40 dark:border-white/10 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#19B896]/15 text-[#0D5A51] dark:text-emerald-300">
                                <i class="las la-broadcast-tower text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-900 dark:text-white">Live USSD &amp; Billing Engine</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Africa&apos;s Talking Webhook Ready</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-[#19B896]/20 px-3 py-1 text-xs font-semibold text-[#0D5A51] dark:text-emerald-300">
                            Active
                        </span>
                    </div>

                    <div class="rounded-2xl bg-slate-900/90 p-5 text-white shadow-inner space-y-3 font-mono text-xs sm:text-sm">
                        <div class="flex items-center justify-between text-emerald-300">
                            <span>Shortcode: *347*102#</span>
                            <span class="text-[11px] bg-emerald-500/20 px-2 py-0.5 rounded-full">CON Session</span>
                        </div>
                        <div class="rounded-xl bg-white/10 p-3.5 leading-relaxed text-slate-100">
                            CON Welcome to Q4 Services<br />
                            1. Instant Games &amp; Wallet<br />
                            2. Airtime Top-Up<br />
                            3. Contact Support
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-slate-300 pt-1">
                            <span>Tier Billing Model:</span>
                            <span class="text-emerald-300 font-semibold">AT Cost × Plan Multiplier</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="glass-pill rounded-2xl p-3.5">
                            <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">Basic Tier</p>
                            <p class="text-lg font-bold text-slate-900 dark:text-white mt-0.5">USSD × 4</p>
                        </div>
                        <div class="glass-pill rounded-2xl p-3.5">
                            <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">Max Tier</p>
                            <p class="text-lg font-bold text-[#127D66] dark:text-emerald-300 mt-0.5">USSD × 2.5</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Services Section -->
    <section id="services" class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="glass-pill inline-block rounded-full px-4 py-1 text-xs font-semibold uppercase tracking-wider text-[#127D66] dark:text-emerald-300 mb-3">
                Core Capabilities
            </span>
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white">
                Built for Enterprise USSD, Messaging &amp; API Teams
            </h2>
            <p class="mt-3 text-slate-600 dark:text-slate-300">
                Everything you need to design interactive telecom journeys, fund wallets instantly, and monitor live session billing.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="glass-card rounded-3xl p-6 transition-transform duration-300 hover:-translate-y-1">
                <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#19B896]/15">
                    <img src="{{ asset('assets/q4/command.svg') }}" alt="USSD Menu Builder" class="h-6 w-6" />
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Visual USSD Menu Builder</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                    Create multi-level CON/END flows, back navigation (<code class="font-semibold">0. Back</code>), and external API webhook forwarding from one workspace.
                </p>
            </div>

            <div class="glass-card rounded-3xl p-6 transition-transform duration-300 hover:-translate-y-1">
                <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#19B896]/15">
                    <img src="{{ asset('assets/q4/activity.svg') }}" alt="Bulk SMS" class="h-6 w-6" />
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Transactional Bulk SMS</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                    Dispatch instant OTPs, alerts, and notifications with custom Sender IDs at transparent 1x base pricing — never inflated by USSD multipliers.
                </p>
            </div>

            <div class="glass-card rounded-3xl p-6 transition-transform duration-300 hover:-translate-y-1">
                <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#19B896]/15">
                    <img src="{{ asset('assets/q4/cloud-card.svg') }}" alt="Airtime & Wallets" class="h-6 w-6" />
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Automated Airtime &amp; Wallets</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                    Programmatic airtime disbursements and real-time wallet top-ups powered by Paystack and Squad with full webhook reconciliation.
                </p>
            </div>

            <div class="glass-card rounded-3xl p-6 transition-transform duration-300 hover:-translate-y-1">
                <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#19B896]/15">
                    <img src="{{ asset('assets/q4/pie-chart.svg') }}" alt="Session Analytics" class="h-6 w-6" />
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Real-Time Session Logs</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                    Track MSISDNs, user input breadcrumbs, delivery status, and exact billed USSD session charges with downloadable CSV reports.
                </p>
            </div>

            <div class="glass-card rounded-3xl p-6 transition-transform duration-300 hover:-translate-y-1">
                <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#19B896]/15">
                    <img src="{{ asset('assets/q4/world.svg') }}" alt="Developer APIs" class="h-6 w-6" />
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">API Keys &amp; Team Access</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                    Issue scoped developer keys for backend apps and collaborate securely with role-based team member permissions.
                </p>
            </div>

            <div class="glass-card rounded-3xl p-6 transition-transform duration-300 hover:-translate-y-1">
                <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#19B896]/15">
                    <i class="las la-shield-alt text-3xl text-[#127D66] dark:text-emerald-300"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Authenticator 2FA Security</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                    Protect admin and enterprise accounts using RFC 6238 Authenticator App two-factor verification and hashed credentials.
                </p>
            </div>
        </div>
    </section>

    <!-- Original Q4 Pricing Plans with USSD Multipliers -->
    <section id="pricing" class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="glass-pill inline-block rounded-full px-4 py-1 text-xs font-semibold uppercase tracking-wider text-[#127D66] dark:text-emerald-300 mb-3">
                Transparent Q4 Pricing Plans
            </span>
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white">
                Choose the Right Plan for Your USSD Volume
            </h2>
            <p class="mt-3 text-slate-600 dark:text-slate-300">
                Each plan sets your USSD multiplier on Africa&apos;s Talking session charges. Upgrade tiers to lower your per-session cost.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
            <!-- Basic Plan (X4) -->
            <div class="glass-card rounded-3xl p-6 flex flex-col justify-between transition-all hover:-translate-y-1">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Starter Tier</span>
                        <span class="rounded-full bg-slate-900/10 dark:bg-white/10 px-3 py-1 text-xs font-bold text-slate-800 dark:text-slate-200">
                            USSD × 4
                        </span>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Basic Plan</h3>
                    <div class="my-5 pb-5 border-b border-slate-300/40 dark:border-white/10">
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-4xl font-extrabold text-[#0D5A51] dark:text-emerald-300">X4</span>
                            <span class="text-sm text-slate-600 dark:text-slate-300">Africa&apos;s Talking USSD cost</span>
                        </div>
                    </div>
                    <ul class="space-y-3 text-sm text-slate-600 dark:text-slate-300">
                        <li class="flex items-center gap-2"><i class="las la-check-circle text-[#19B896] text-lg"></i> USSD AT Charge × 4</li>
                        <li class="flex items-center gap-2"><i class="las la-check-circle text-[#19B896] text-lg"></i> SMS &amp; Airtime at 1x base rate</li>
                        <li class="flex items-center gap-2"><i class="las la-check-circle text-[#19B896] text-lg"></i> Visual USSD Menu Builder</li>
                        <li class="flex items-center gap-2"><i class="las la-check-circle text-[#19B896] text-lg"></i> Instant Wallet Top-up</li>
                    </ul>
                </div>
                <a href="{{ route('register') }}"
                    class="mt-8 block w-full rounded-full border border-[#0D5A51]/40 py-2.5 text-center text-sm font-semibold text-[#0D5A51] dark:text-emerald-300 hover:bg-[#0D5A51] hover:text-white transition-all">
                    Start with Basic
                </a>
            </div>

            <!-- Plus Plan (X3.5) -->
            <div class="glass-card rounded-3xl p-6 flex flex-col justify-between ring-2 ring-[#19B896]/60 transition-all hover:-translate-y-1">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#127D66] dark:text-emerald-300">Popular</span>
                        <span class="rounded-full bg-[#19B896]/20 px-3 py-1 text-xs font-bold text-[#0D5A51] dark:text-emerald-300">
                            USSD × 3.5
                        </span>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Plus Plan</h3>
                    <div class="my-5 pb-5 border-b border-slate-300/40 dark:border-white/10">
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-4xl font-extrabold text-[#0D5A51] dark:text-emerald-300">X3.5</span>
                            <span class="text-sm text-slate-600 dark:text-slate-300">Africa&apos;s Talking USSD cost</span>
                        </div>
                    </div>
                    <ul class="space-y-3 text-sm text-slate-600 dark:text-slate-300">
                        <li class="flex items-center gap-2"><i class="las la-check-circle text-[#19B896] text-lg"></i> Lower USSD AT Charge × 3.5</li>
                        <li class="flex items-center gap-2"><i class="las la-check-circle text-[#19B896] text-lg"></i> SMS &amp; Airtime at 1x base rate</li>
                        <li class="flex items-center gap-2"><i class="las la-check-circle text-[#19B896] text-lg"></i> External API Node Forwarding</li>
                        <li class="flex items-center gap-2"><i class="las la-check-circle text-[#19B896] text-lg"></i> Team Members &amp; CSV Exports</li>
                    </ul>
                </div>
                <a href="{{ route('register') }}"
                    class="mt-8 block w-full rounded-full bg-[#0D5A51] py-2.5 text-center text-sm font-semibold text-white shadow-md shadow-[#0D5A51]/25 hover:bg-[#127D66] transition-all">
                    Choose Plus Plan
                </a>
            </div>

            <!-- Premium Plan (X2.9) -->
            <div class="glass-card rounded-3xl p-6 flex flex-col justify-between transition-all hover:-translate-y-1">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Growth Tier</span>
                        <span class="rounded-full bg-[#4371e9]/15 px-3 py-1 text-xs font-bold text-[#4371e9] dark:text-blue-300">
                            USSD × 2.9
                        </span>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Premium Plan</h3>
                    <div class="my-5 pb-5 border-b border-slate-300/40 dark:border-white/10">
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-4xl font-extrabold text-[#0D5A51] dark:text-emerald-300">X2.9</span>
                            <span class="text-sm text-slate-600 dark:text-slate-300">Africa&apos;s Talking USSD cost</span>
                        </div>
                    </div>
                    <ul class="space-y-3 text-sm text-slate-600 dark:text-slate-300">
                        <li class="flex items-center gap-2"><i class="las la-check-circle text-[#19B896] text-lg"></i> Discounted USSD AT Charge × 2.9</li>
                        <li class="flex items-center gap-2"><i class="las la-check-circle text-[#19B896] text-lg"></i> SMS &amp; Airtime at 1x base rate</li>
                        <li class="flex items-center gap-2"><i class="las la-check-circle text-[#19B896] text-lg"></i> Dedicated Shortcode Routing</li>
                        <li class="flex items-center gap-2"><i class="las la-check-circle text-[#19B896] text-lg"></i> Priority Engineering Support</li>
                    </ul>
                </div>
                <a href="{{ route('register') }}"
                    class="mt-8 block w-full rounded-full border border-[#0D5A51]/40 py-2.5 text-center text-sm font-semibold text-[#0D5A51] dark:text-emerald-300 hover:bg-[#0D5A51] hover:text-white transition-all">
                    Select Premium
                </a>
            </div>

            <!-- Max Plan (X2.5) -->
            <div class="glass-card rounded-3xl p-6 flex flex-col justify-between transition-all hover:-translate-y-1">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-300">Enterprise</span>
                        <span class="rounded-full bg-amber-500/20 px-3 py-1 text-xs font-bold text-amber-700 dark:text-amber-300">
                            USSD × 2.5
                        </span>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Max Plan</h3>
                    <div class="my-5 pb-5 border-b border-slate-300/40 dark:border-white/10">
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-4xl font-extrabold text-[#0D5A51] dark:text-emerald-300">X2.5</span>
                            <span class="text-sm text-slate-600 dark:text-slate-300">Africa&apos;s Talking USSD cost</span>
                        </div>
                    </div>
                    <ul class="space-y-3 text-sm text-slate-600 dark:text-slate-300">
                        <li class="flex items-center gap-2"><i class="las la-check-circle text-[#19B896] text-lg"></i> Lowest USSD AT Charge × 2.5</li>
                        <li class="flex items-center gap-2"><i class="las la-check-circle text-[#19B896] text-lg"></i> SMS &amp; Airtime at 1x base rate</li>
                        <li class="flex items-center gap-2"><i class="las la-check-circle text-[#19B896] text-lg"></i> High-Volume SLA &amp; Webhooks</li>
                        <li class="flex items-center gap-2"><i class="las la-check-circle text-[#19B896] text-lg"></i> Dedicated Account Manager</li>
                    </ul>
                </div>
                <a href="{{ route('register') }}"
                    class="mt-8 block w-full rounded-full border border-[#0D5A51]/40 py-2.5 text-center text-sm font-semibold text-[#0D5A51] dark:text-emerald-300 hover:bg-[#0D5A51] hover:text-white transition-all">
                    Go Max Enterprise
                </a>
            </div>
        </div>

        <p class="mt-6 text-center text-xs sm:text-sm text-slate-600 dark:text-slate-300">
            <strong>Billing Transparency:</strong> Tier multipliers (<strong>X4</strong> Basic, <strong>X3.5</strong> Plus, <strong>X2.9</strong> Premium, <strong>X2.5</strong> Max) apply strictly to Africa&apos;s Talking USSD session costs. SMS and Airtime remain at base rates.
        </p>
    </section>

    <!-- Why Choose Q4 & Trust Section -->
    <section id="why-q4" class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white">
                Why Businesses Trust Q4 International
            </h2>
            <p class="mt-3 text-slate-600 dark:text-slate-300">
                Engineered for high reliability, cryptographic security, and responsive local support.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="glass-card rounded-3xl p-6 text-center space-y-4">
                <img src="{{ asset('assets/q4/security.svg') }}" alt="Security" class="mx-auto h-28 w-auto object-contain" />
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Bank-Grade Security</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    End-to-end TLS encryption, Authenticator 2FA protection, and isolated API keys for every client organization.
                </p>
            </div>

            <div class="glass-card rounded-3xl p-6 text-center space-y-4">
                <img src="{{ asset('assets/q4/reliability.svg') }}" alt="Reliability" class="mx-auto h-28 w-auto object-contain" />
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Proven Reliability</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Automated session recovery and direct Africa&apos;s Talking callback &amp; event webhooks built for sustained traffic spikes.
                </p>
            </div>

            <div class="glass-card rounded-3xl p-6 text-center space-y-4">
                <img src="{{ asset('assets/q4/support.svg') }}" alt="Support" class="mx-auto h-28 w-auto object-contain" />
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">24/7 Dedicated Support</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Fast engineering onboarding for custom USSD trees, external REST integrations, and telco sender ID registrations.
                </p>
            </div>
        </div>
    </section>

    <!-- FAQ Accordion -->
    <section id="faq" class="mx-auto max-w-4xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="text-center mb-10">
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white">
                Frequently Asked Questions
            </h2>
        </div>

        @php
            $faqs = [
                [
                    'q' => 'What services does Q4 International provide?',
                    'a' => 'Q4 International provides programmable USSD shortcodes and visual menu builders, high-delivery transactional Bulk SMS, automated Airtime disbursement, and unified developer APIs.'
                ],
                [
                    'q' => 'How is my USSD billing calculated from Africa\'s Talking?',
                    'a' => 'USSD session charges reported from Africa\'s Talking are multiplied based on your plan tier: Basic Plan (X4), Plus Plan (X3.5), Premium Plan (X2.9), and Max Plan (X2.5). Bulk SMS and Airtime remain at base rates.'
                ],
                [
                    'q' => 'Can I connect my own backend API to a Q4 USSD menu?',
                    'a' => 'Yes. You can configure API nodes inside the visual USSD Menu Builder or point Africa\'s Talking webhooks directly to our high-availability gateway for dynamic menu responses.'
                ],
                [
                    'q' => 'How do I fund my Q4 wallet balance?',
                    'a' => 'Sign in to your portal and navigate to Billing & Wallet to fund instantly online via Paystack or Squad.'
                ],
                [
                    'q' => 'Which mobile networks are supported?',
                    'a' => 'We support MTN, Airtel, Glo, and 9mobile across USSD menus, Bulk SMS delivery, and instant Airtime VTU transfers.'
                ],
                [
                    'q' => 'How is my portal account protected?',
                    'a' => 'All accounts support Authenticator App Two-Factor Authentication (2FA), scoped API credentials, and team member role controls.'
                ],
                [
                    'q' => 'Can I upgrade my pricing tier anytime?',
                    'a' => 'Yes. Contact our support team or portal administrator to move between Basic (X4), Plus (X3.5), Premium (X2.9), and Max (X2.5) tiers at any time.'
                ],
            ];
        @endphp

        <div class="space-y-3" id="q4-faq-list">
            @foreach ($faqs as $index => $faq)
                <div class="glass-card rounded-2xl overflow-hidden">
                    <button type="button"
                        class="faq-toggle flex w-full items-center justify-between gap-4 px-6 py-4 text-left font-semibold text-slate-900 dark:text-white"
                        aria-expanded="{{ $index === 0 ? 'true' : 'false' }}">
                        <span>{{ $faq['q'] }}</span>
                        <i class="las {{ $index === 0 ? 'la-minus' : 'la-plus' }} text-xl text-[#127D66] dark:text-emerald-300 shrink-0"></i>
                    </button>
                    <div class="faq-body px-6 pb-5 text-sm text-slate-600 dark:text-slate-300 {{ $index === 0 ? '' : 'hidden' }}">
                        {{ $faq['a'] }}
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <!-- Dark Emerald Glass Footer -->
    <footer class="mt-10 bg-[#0D5A51]/95 backdrop-blur-xl text-white border-t border-white/15">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-start">
                <div class="space-y-4">
                    <img src="{{ asset('assets/images/q4-logo-white.png') }}" alt="Q4 International"
                        class="h-11 w-auto object-contain" />
                    <p class="text-sm text-emerald-100/85 max-w-xs leading-relaxed">
                        Enterprise USSD automation, messaging infrastructure, and telecom payment orchestration by Q4 International.
                    </p>
                </div>

                <div class="space-y-3">
                    <h4 class="text-base font-bold text-white">Platform Links</h4>
                    <ul class="space-y-2 text-sm text-emerald-100/85">
                        <li><a href="#services" class="hover:text-white">Services</a></li>
                        <li><a href="#pricing" class="hover:text-white">Pricing Plans (X4 / X3.5 / X2.9 / X2.5)</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-white">Client &amp; Admin Sign In</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-white">Create Account</a></li>
                    </ul>
                </div>

                <div class="space-y-3">
                    <h4 class="text-base font-bold text-white">Connect &amp; Support</h4>
                    <p class="text-sm text-emerald-100/85">Email: support@q4iltd.com</p>
                    <div class="flex items-center gap-3 pt-2">
                        <a href="#" aria-label="Instagram"
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition-colors">
                            <img src="{{ asset('assets/q4/instagram.svg') }}" alt="Instagram" class="h-4 w-4 brightness-0 invert" />
                        </a>
                        <a href="#" aria-label="Twitter"
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition-colors">
                            <img src="{{ asset('assets/q4/twitter.svg') }}" alt="Twitter" class="h-4 w-4 brightness-0 invert" />
                        </a>
                        <a href="#" aria-label="YouTube"
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition-colors">
                            <img src="{{ asset('assets/q4/youtube.svg') }}" alt="YouTube" class="h-4 w-4 brightness-0 invert" />
                        </a>
                    </div>
                </div>
            </div>

            <div class="mt-10 border-t border-white/15 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-emerald-100/80">
                <p>&copy; {{ date('Y') }} Q4 International. All rights reserved.</p>
                <p>Powered by {{ config('app.name') }}</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const menuBtn = document.getElementById('landing-menu-btn');
            const mobileMenu = document.getElementById('landing-mobile-menu');
            menuBtn && mobileMenu && menuBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
                mobileMenu.classList.toggle('flex');
            });

            document.querySelectorAll('.faq-toggle').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const body = btn.nextElementSibling;
                    const icon = btn.querySelector('i');
                    const isOpen = btn.getAttribute('aria-expanded') === 'true';
                    btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                    body && body.classList.toggle('hidden', isOpen);
                    if (icon) {
                        icon.classList.toggle('la-plus', isOpen);
                        icon.classList.toggle('la-minus', !isOpen);
                    }
                });
            });
        });
    </script>
</body>

</html>
