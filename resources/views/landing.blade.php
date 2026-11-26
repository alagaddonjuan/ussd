<!DOCTYPE html>
<html dir="ltr" lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon" />
    <meta name="description"
        content="Q4I Limited — Enterprise USSD menus, Bulk SMS, Airtime, Social Betting Clearinghouse, Digital Payments, PoS & Hosting Infrastructure." />
    @vite(['resources/css/app.css'])
    <title>Q4 International — Enterprise USSD, Messaging &amp; Digital Infrastructure</title>
</head>

<body class="antialiased text-[#1F2937] dark:text-slate-100">
    <!-- Ambient Decorative Q4I Color Orbs (#0878F8, #22C7F8, #6D5EF7) -->
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -top-28 -left-24 h-96 w-96 rounded-full bg-[#0878F8]/20 blur-3xl"></div>
        <div class="absolute top-1/4 -right-24 h-[28rem] w-[28rem] rounded-full bg-[#22C7F8]/18 blur-3xl"></div>
        <div class="absolute -bottom-32 left-1/3 h-96 w-96 rounded-full bg-[#6D5EF7]/14 blur-3xl"></div>
    </div>

    <!-- Top Sharp Glass Navigation -->
    <header class="sticky top-3 z-40 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="glass-card flex items-center justify-between rounded-full px-4 py-3 sm:px-6 transition-all">
            <a href="{{ route('home') }}" class="flex items-center gap-3 shrink-0">
                <img src="{{ asset('assets/images/q4-logo.png') }}" alt="Q4 International"
                    class="h-10 w-auto object-contain dark:hidden" />
                <img src="{{ asset('assets/images/q4-logo-white.png') }}" alt="Q4 International"
                    class="hidden h-10 w-auto object-contain dark:block" />
            </a>

            <nav class="hidden md:flex items-center gap-7 text-sm font-semibold text-[#1F2937] dark:text-slate-200">
                <a href="#services" class="hover:text-[#0878F8] transition-colors">USSD &amp; Messaging</a>
                <a href="#ecosystem" class="hover:text-[#0878F8] transition-colors">All Q4 Services</a>
                <a href="#pricing" class="hover:text-[#0878F8] transition-colors">Pricing Plans</a>
                <a href="#partners" class="hover:text-[#0878F8] transition-colors">Partners</a>
                <a href="#faq" class="hover:text-[#0878F8] transition-colors">FAQ</a>
            </nav>

            <div class="flex items-center gap-2 sm:gap-3">
                @auth
                    <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}"
                        class="inline-flex items-center gap-2 rounded-full bg-[#0878F8] px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-[#0878F8]/30 hover:bg-[#0B3A75] transition-all">
                        <i class="las la-tachometer-alt text-lg"></i>
                        <span>Go to Dashboard</span>
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="hidden sm:inline-flex items-center rounded-full border border-[#0B3A75]/25 px-4 py-2 text-sm font-semibold text-[#0B3A75] dark:text-[#22C7F8] dark:border-[#22C7F8]/35 hover:bg-[#0878F8]/10 transition-all">
                        Sign In
                    </a>
                    <a href="{{ route('register') }}"
                        class="inline-flex items-center gap-2 rounded-full bg-[#0878F8] px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-[#0878F8]/30 hover:bg-[#0B3A75] transition-all">
                        <span>Get Started</span>
                        <i class="las la-arrow-right text-base"></i>
                    </a>
                @endauth

                <button type="button" id="landing-menu-btn"
                    class="md:hidden inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#D1D5DB] dark:border-white/20 text-[#1F2937] dark:text-slate-200"
                    aria-label="Toggle Menu">
                    <i class="las la-bars text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu Dropdown -->
        <div id="landing-mobile-menu" class="glass-card mt-2 hidden flex-col gap-2 rounded-2xl p-4 md:hidden">
            <a href="#services" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-[#0878F8]/10">USSD &amp; Messaging</a>
            <a href="#ecosystem" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-[#0878F8]/10">All Q4 Services</a>
            <a href="#pricing" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-[#0878F8]/10">Pricing Plans</a>
            <a href="#partners" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-[#0878F8]/10">Partners</a>
            <a href="#faq" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-[#0878F8]/10">FAQ</a>
            @guest
                <a href="{{ route('login') }}"
                    class="mt-1 rounded-xl border border-[#0878F8]/30 px-4 py-2 text-center text-sm font-semibold text-[#0878F8]">
                    Sign In
                </a>
            @endguest
        </div>
    </header>

    <!-- Hero Section -->
    <section class="mx-auto max-w-7xl px-4 pt-12 pb-14 sm:px-6 lg:px-8 lg:pt-16">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            <div class="lg:col-span-7 space-y-6">
                <div
                    class="glass-pill inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-[#0B3A75] dark:text-[#22C7F8]">
                    <span class="inline-block h-2 w-2 rounded-full bg-[#10B981]"></span>
                    <span>Q4 International Digital Infrastructure</span>
                </div>

                <h1 class="text-4xl font-extrabold tracking-tight text-[#0B3A75] dark:text-white sm:text-5xl lg:text-6xl leading-[1.08]">
                    Power Seamless Business <span class="text-[#0878F8]">Communication</span> &amp; <span class="text-[#6D5EF7]">Payments</span>
                </h1>

                <p class="max-w-2xl text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed">
                    Launch interactive USSD applications, dispatch Bulk SMS, automate Airtime top-ups, and run enterprise social betting, digital payments, PoS, CRM, and cloud infrastructure — built by Q4I Limited.
                </p>

                <div class="flex flex-wrap items-center gap-4 pt-2">
                    @auth
                        <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}"
                            class="inline-flex items-center gap-2 rounded-full bg-[#0878F8] px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-[#0878F8]/30 hover:bg-[#0B3A75] transition-all">
                            <span>Open Console</span>
                            <i class="las la-arrow-right"></i>
                        </a>
                    @else
                        <a href="{{ route('register') }}"
                            class="inline-flex items-center gap-2 rounded-full bg-[#0878F8] px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-[#0878F8]/30 hover:bg-[#0B3A75] transition-all">
                            <span>Create Client Account</span>
                            <i class="las la-arrow-right"></i>
                        </a>
                        <a href="{{ route('login') }}"
                            class="glass-pill inline-flex items-center gap-2 rounded-full px-6 py-3.5 text-base font-semibold text-[#0B3A75] dark:text-white hover:border-[#0878F8] transition-all">
                            <i class="las la-sign-in-alt text-lg text-[#0878F8]"></i>
                            <span>Client / Admin Sign In</span>
                        </a>
                    @endauth
                    <a href="#pricing"
                        class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#0878F8] dark:text-[#22C7F8] hover:underline">
                        <span>View Telco Rates</span>
                        <i class="las la-angle-down"></i>
                    </a>
                </div>

                <!-- Sharp KPI Row -->
                <div class="grid grid-cols-3 gap-4 pt-4 max-w-xl">
                    <div class="glass-card rounded-2xl p-4 border-t-2 border-[#0878F8]">
                        <p class="text-2xl font-extrabold text-[#0878F8] dark:text-[#22C7F8]">99.98%</p>
                        <p class="text-xs font-medium text-slate-600 dark:text-slate-300 mt-1">Gateway Uptime</p>
                    </div>
                    <div class="glass-card rounded-2xl p-4 border-t-2 border-[#10B981]">
                        <p class="text-2xl font-extrabold text-[#10B981]">&lt; 250ms</p>
                        <p class="text-xs font-medium text-slate-600 dark:text-slate-300 mt-1">USSD Latency</p>
                    </div>
                    <div class="glass-card rounded-2xl p-4 border-t-2 border-[#6D5EF7]">
                        <p class="text-2xl font-extrabold text-[#6D5EF7] dark:text-purple-300">4 Telcos</p>
                        <p class="text-xs font-medium text-slate-600 dark:text-slate-300 mt-1">MTN, Airtel, Glo, T2</p>
                    </div>
                </div>
            </div>

            <!-- Hero Frosted Glass Preview: Q4 Services (*384*23#) -->
            <div class="lg:col-span-5">
                <div class="glass-card relative rounded-3xl p-6 sm:p-8 space-y-5">
                    <div class="flex items-center justify-between border-b border-[#D1D5DB]/70 dark:border-white/10 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#0878F8]/12 text-[#0878F8] dark:text-[#22C7F8]">
                                <i class="las la-broadcast-tower text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-[#0B3A75] dark:text-white">Q4 Services — USSD Console</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Direct Multi-Carrier Routing</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-[#10B981]/15 px-3 py-1 text-xs font-bold text-[#10B981]">
                            Live
                        </span>
                    </div>

                    <div class="rounded-2xl bg-[#0B3A75] p-5 text-white shadow-inner space-y-3 font-mono text-xs sm:text-sm border border-[#22C7F8]/25">
                        <div class="flex items-center justify-between text-[#22C7F8]">
                            <span>Shortcode: *384*23#</span>
                            <span class="text-[11px] bg-[#0878F8]/35 px-2.5 py-0.5 rounded-full text-white">CON Session</span>
                        </div>
                        <div class="rounded-xl bg-white/10 p-3.5 leading-relaxed text-slate-100">
                            CON Welcome to Q4 Services<br />
                            1. Check Account Balance<br />
                            2. Purchase Airtime<br />
                            3. Social Betting &amp; Wallet<br />
                            4. Contact Support
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-slate-200 pt-1">
                            <span>Session Billing:</span>
                            <span class="text-[#22C7F8] font-semibold">20s Interval (Glo 120s)</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="glass-pill rounded-2xl p-3.5">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Starting MTN Rate</p>
                            <p class="text-lg font-extrabold text-[#0878F8] dark:text-[#22C7F8] mt-0.5">₦18.75 <span class="text-xs font-normal text-slate-500">/ 20s</span></p>
                        </div>
                        <div class="glass-pill rounded-2xl p-3.5">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Monthly Maintenance</p>
                            <p class="text-lg font-extrabold text-[#0B3A75] dark:text-white mt-0.5">₦159,500</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trusted By Partners Marquee (From q4iltd.com, white-labeled cleanly) -->
    <section id="partners" class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="glass-card rounded-3xl px-6 py-6 overflow-hidden">
            <p class="text-center text-xs font-bold uppercase tracking-[0.2em] text-[#0B3A75] dark:text-[#22C7F8] mb-5">
                Trusted By Leading Enterprises &amp; Partners
            </p>
            @php
                $partners = [
                    ['file' => 'modern-lottery.jpg', 'name' => 'Modern Lottery'],
                    ['file' => 'kozen.jpg', 'name' => 'Kozen'],
                    ['file' => 'netplusdotcom.png', 'name' => 'NetPlusDotCom'],
                    ['file' => 'omni.png', 'name' => 'Omni'],
                    ['file' => 'urovo.jpg', 'name' => 'Urovo'],
                    ['file' => 'rexpay.png', 'name' => 'Rexpay'],
                    ['file' => 'zenith.png', 'name' => 'Zenith'],
                    ['file' => 'loolas-pod.png', 'name' => 'Loolas POD'],
                    ['file' => 'western-lotto.jpg', 'name' => 'Western Lotto'],
                    ['file' => 'hoco.png', 'name' => 'Ho+Co'],
                    ['file' => 'igi.jpg', 'name' => 'IGI'],
                ];
            @endphp
            <div class="relative overflow-hidden">
                <div class="q4-partners-track items-center gap-10">
                    @for ($loopCopy = 0; $loopCopy < 2; $loopCopy++)
                        @foreach ($partners as $partner)
                            <div class="flex h-12 w-32 shrink-0 items-center justify-center rounded-xl bg-white/95 px-3 py-1.5 shadow-xs border border-[#D1D5DB]/60">
                                <img src="{{ asset('assets/partners/' . $partner['file']) }}" alt="{{ $partner['name'] }}"
                                    class="max-h-9 max-w-full object-contain" loading="lazy" />
                            </div>
                        @endforeach
                    @endfor
                </div>
            </div>
        </div>
    </section>

    <!-- Core Communications Capabilities -->
    <section id="services" class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="glass-pill inline-block rounded-full px-4 py-1 text-xs font-bold uppercase tracking-wider text-[#0878F8] dark:text-[#22C7F8] mb-3">
                Core USSD &amp; Messaging Suite
            </span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-[#0B3A75] dark:text-white">
                Built for Enterprise USSD, SMS &amp; API Teams
            </h2>
            <p class="mt-3 text-slate-600 dark:text-slate-300">
                Design interactive telecom journeys in seconds with Text-to-Menu building, fund wallets via Paystack, and monitor live session telemetry.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="glass-card rounded-3xl p-6 transition-transform duration-300 hover:-translate-y-1">
                <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#0878F8]/12 text-[#0878F8]">
                    <i class="las la-sitemap text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-[#0B3A75] dark:text-white mb-2">Text &amp; Visual USSD Builder</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                    Build multi-level CON/END menus from plain indented text in seconds, with <code class="font-semibold text-[#0878F8]">0. Back</code> navigation, external API forwarding, and live simulator testing.
                </p>
            </div>

            <div class="glass-card rounded-3xl p-6 transition-transform duration-300 hover:-translate-y-1">
                <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#22C7F8]/15 text-[#0878F8]">
                    <i class="las la-sms text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-[#0B3A75] dark:text-white mb-2">Transactional Bulk SMS</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                    Dispatch instant OTPs, alerts, and notifications with registered Sender IDs and transparent flat per-message pricing.
                </p>
            </div>

            <div class="glass-card rounded-3xl p-6 transition-transform duration-300 hover:-translate-y-1">
                <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#10B981]/15 text-[#10B981]">
                    <i class="las la-wallet text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-[#0B3A75] dark:text-white mb-2">Automated Airtime &amp; Wallets</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                    Programmatic airtime disbursements, instant Paystack wallet funding, customizable low-balance email alerts, and monthly maintenance automation.
                </p>
            </div>

            <div class="glass-card rounded-3xl p-6 transition-transform duration-300 hover:-translate-y-1">
                <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#6D5EF7]/15 text-[#6D5EF7]">
                    <i class="las la-chart-bar text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-[#0B3A75] dark:text-white mb-2">Duration &amp; Hop Telemetry</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                    Track MSISDNs, carrier networks, exact session durations, hop counts, and per-session charges with 1-click CSV report exports.
                </p>
            </div>

            <div class="glass-card rounded-3xl p-6 transition-transform duration-300 hover:-translate-y-1">
                <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#0B3A75]/12 text-[#0B3A75] dark:text-[#22C7F8]">
                    <i class="las la-key text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-[#0B3A75] dark:text-white mb-2">API Keys, Docs &amp; Teams</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                    Copy-ready code examples in cURL, PHP, Node.js &amp; Python, IP-whitelisted developer API keys, and role-based team member management.
                </p>
            </div>

            <div class="glass-card rounded-3xl p-6 transition-transform duration-300 hover:-translate-y-1">
                <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#10B981]/15 text-[#10B981]">
                    <i class="las la-shield-alt text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-[#0B3A75] dark:text-white mb-2">Authenticator 2FA Security</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                    Enterprise accounts protected by Authenticator App 2FA verification, TLS encryption, and immutable security controls.
                </p>
            </div>
        </div>
    </section>

    <!-- Full Q4 International Ecosystem Services (From q4iltd.com & lotto.q4iltd.com) -->
    <section id="ecosystem" class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="glass-pill inline-block rounded-full px-4 py-1 text-xs font-bold uppercase tracking-wider text-[#6D5EF7] dark:text-purple-300 mb-3">
                Complete Q4I Product Ecosystem
            </span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-[#0B3A75] dark:text-white">
                More Solutions From Q4 International
            </h2>
            <p class="mt-3 text-slate-600 dark:text-slate-300">
                Beyond USSD &amp; Messaging, Q4I powers digital payments, social betting infrastructure, PoS fleets, CRM automation, and cloud hosting.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Featured: Social Betting & Financial Engine (lotto.q4iltd.com) -->
            <a href="https://lotto.q4iltd.com" target="_blank" rel="noopener noreferrer"
                class="glass-card rounded-3xl p-6 md:col-span-2 border-l-4 border-[#6D5EF7] flex flex-col justify-between transition-all hover:-translate-y-1">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="inline-flex items-center gap-2 rounded-full bg-[#6D5EF7]/15 px-3 py-1 text-xs font-bold text-[#6D5EF7] dark:text-purple-300">
                            <i class="las la-dice text-base"></i> Social Betting &amp; Gaming Engine
                        </span>
                        <span class="text-xs font-semibold text-[#0878F8] dark:text-[#22C7F8]">lotto.q4iltd.com &rarr;</span>
                    </div>
                    <h3 class="text-xl font-bold text-[#0B3A75] dark:text-white mb-2">
                        Q4I Financial Engine &amp; Social Betting Clearinghouse
                    </h3>
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                        Real-time 35% / 65% escrow split settlement, HMAC-SHA256 cryptographic ledger, dual-officer Maker-Checker draw governance, and integrated USSD/social gaming channels for licensed operators.
                    </p>
                </div>
                <span class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-[#6D5EF7] dark:text-purple-300">
                    Explore Social Betting Clearinghouse <i class="las la-external-link-alt"></i>
                </span>
            </a>

            <a href="https://q4iltd.com" target="_blank" rel="noopener noreferrer"
                class="glass-card rounded-3xl p-6 flex flex-col justify-between transition-all hover:-translate-y-1">
                <div>
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-[#0878F8]/12 text-[#0878F8]">
                        <i class="las la-laptop-code text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-bold text-[#0B3A75] dark:text-white mb-1.5">Innovative Software</h4>
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        Custom full-stack web platforms, core enterprise backends, and conversational API integrations.
                    </p>
                </div>
                <span class="mt-4 text-xs font-semibold text-[#0878F8]">q4iltd.com &rarr;</span>
            </a>

            <a href="https://host.q4iltd.com/" target="_blank" rel="noopener noreferrer"
                class="glass-card rounded-3xl p-6 flex flex-col justify-between transition-all hover:-translate-y-1">
                <div>
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-[#22C7F8]/15 text-[#0878F8]">
                        <i class="las la-server text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-bold text-[#0B3A75] dark:text-white mb-1.5">Secure Hosting</h4>
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        Managed cloud hosting, WHMCS reseller infrastructure, and domain management built for high uptime.
                    </p>
                </div>
                <span class="mt-4 text-xs font-semibold text-[#0878F8]">host.q4iltd.com &rarr;</span>
            </a>

            <a href="https://q4iltd.com" target="_blank" rel="noopener noreferrer"
                class="glass-card rounded-3xl p-6 flex flex-col justify-between transition-all hover:-translate-y-1">
                <div>
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-[#0B3A75]/12 text-[#0B3A75] dark:text-[#22C7F8]">
                        <i class="las la-credit-card text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-bold text-[#0B3A75] dark:text-white mb-1.5">PoS &amp; Hardware</h4>
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        Diagnostic workflows, hardware provisioning, and maintenance across Kozen &amp; Urovo PoS terminal fleets.
                    </p>
                </div>
                <span class="mt-4 text-xs font-semibold text-[#0878F8]">Enterprise Hardware &rarr;</span>
            </a>

            <a href="https://crm.q4iltd.com/authentication/login" target="_blank" rel="noopener noreferrer"
                class="glass-card rounded-3xl p-6 flex flex-col justify-between transition-all hover:-translate-y-1">
                <div>
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-[#6D5EF7]/15 text-[#6D5EF7]">
                        <i class="las la-robot text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-bold text-[#0B3A75] dark:text-white mb-1.5">Custom CRM Automation</h4>
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        Automated customer workflows, live support ticketing, and sales pipeline orchestration tailored to your team.
                    </p>
                </div>
                <span class="mt-4 text-xs font-semibold text-[#0878F8]">crm.q4iltd.com &rarr;</span>
            </a>

            <a href="https://pay.q4iltd.com/merchant/login" target="_blank" rel="noopener noreferrer"
                class="glass-card rounded-3xl p-6 flex flex-col justify-between transition-all hover:-translate-y-1">
                <div>
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-[#10B981]/15 text-[#10B981]">
                        <i class="las la-money-check-alt text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-bold text-[#0B3A75] dark:text-white mb-1.5">Digital Payments</h4>
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        High-reliability payment routing, settlement gateways, and financial compliance solutions for merchants.
                    </p>
                </div>
                <span class="mt-4 text-xs font-semibold text-[#0878F8]">pay.q4iltd.com &rarr;</span>
            </a>

            <a href="https://pay.q4iltd.com/vendor/login" target="_blank" rel="noopener noreferrer"
                class="glass-card rounded-3xl p-6 flex flex-col justify-between transition-all hover:-translate-y-1">
                <div>
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-[#F59E0B]/15 text-[#F59E0B]">
                        <i class="las la-store text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-bold text-[#0B3A75] dark:text-white mb-1.5">Social Commerce &amp; Tools</h4>
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        Digital vendor storefronts with integrated checkouts plus free Chrome browser productivity extensions.
                    </p>
                </div>
                <span class="mt-4 text-xs font-semibold text-[#0878F8]">Vendor Portal &rarr;</span>
            </a>
        </div>
    </section>

    <!-- Exact Q4 Pricing Plans (Exact Per-Telco Interval Rates + ₦159,500 Maintenance) -->
    <section id="pricing" class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="glass-pill inline-block rounded-full px-4 py-1 text-xs font-bold uppercase tracking-wider text-[#0878F8] dark:text-[#22C7F8] mb-3">
                Transparent Q4 Pricing Plans
            </span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-[#0B3A75] dark:text-white">
                Choose the Right Plan for Your USSD Volume
            </h2>
            <p class="mt-3 text-slate-600 dark:text-slate-300">
                Sessions on MTN, Airtel, and T2 (9mobile) are billed per 20-second interval; Glo sessions are billed per 120 seconds. Active USSD plans include a ₦159,500 monthly maintenance fee.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
            <!-- Basic Plan -->
            <div class="glass-card rounded-3xl p-6 flex flex-col justify-between transition-all hover:-translate-y-1">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Starter Tier</span>
                        <span class="rounded-full bg-[#0B3A75]/10 dark:bg-white/10 px-3 py-1 text-xs font-bold text-[#0B3A75] dark:text-slate-200">
                            ₦159,500 / mo Maint.
                        </span>
                    </div>
                    <h3 class="text-2xl font-bold text-[#0B3A75] dark:text-white">Basic Plan</h3>
                    <div class="my-4 pb-4 border-b border-[#D1D5DB]/70 dark:border-white/10">
                        <p class="text-2xl font-extrabold text-[#0878F8] dark:text-[#22C7F8]">₦0 – ₦199,999</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Monthly session spend tier</p>
                    </div>
                    <ul class="space-y-3 text-sm text-[#1F2937] dark:text-slate-200">
                        <li class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2"><i class="las la-check-circle text-[#10B981] text-lg"></i> MTN <span class="text-xs text-slate-500">(20s)</span></span>
                            <span class="font-bold">₦30.00</span>
                        </li>
                        <li class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2"><i class="las la-check-circle text-[#10B981] text-lg"></i> Airtel <span class="text-xs text-slate-500">(20s)</span></span>
                            <span class="font-bold">₦19.56</span>
                        </li>
                        <li class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2"><i class="las la-check-circle text-[#10B981] text-lg"></i> Glo <span class="text-xs text-slate-500">(120s)</span></span>
                            <span class="font-bold">₦48.00</span>
                        </li>
                        <li class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2"><i class="las la-check-circle text-[#10B981] text-lg"></i> T2 / 9mobile <span class="text-xs text-slate-500">(20s)</span></span>
                            <span class="font-bold">₦18.00</span>
                        </li>
                        <li class="flex items-center gap-2 pt-2 border-t border-[#D1D5DB]/50 dark:border-white/10 text-xs text-slate-600 dark:text-slate-300">
                            <i class="las la-tools text-[#0878F8] text-base"></i> Maintenance: ₦159,500 / month
                        </li>
                        <li class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                            <i class="las la-sms text-[#0878F8] text-base"></i> Bulk SMS &amp; Airtime Included
                        </li>
                    </ul>
                </div>
                <a href="{{ route('register') }}"
                    class="mt-8 block w-full rounded-full border border-[#0878F8]/50 py-2.5 text-center text-sm font-semibold text-[#0878F8] dark:text-[#22C7F8] hover:bg-[#0878F8] hover:text-white transition-all">
                    Start with Basic
                </a>
            </div>

            <!-- Plus Plan -->
            <div class="glass-card rounded-3xl p-6 flex flex-col justify-between ring-2 ring-[#0878F8]/70 transition-all hover:-translate-y-1">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#0878F8] dark:text-[#22C7F8]">Popular</span>
                        <span class="rounded-full bg-[#0878F8]/15 px-3 py-1 text-xs font-bold text-[#0878F8] dark:text-[#22C7F8]">
                            ₦159,500 / mo Maint.
                        </span>
                    </div>
                    <h3 class="text-2xl font-bold text-[#0B3A75] dark:text-white">Plus Plan</h3>
                    <div class="my-4 pb-4 border-b border-[#D1D5DB]/70 dark:border-white/10">
                        <p class="text-2xl font-extrabold text-[#0878F8] dark:text-[#22C7F8]">₦200,000 – ₦599,999</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Monthly session spend tier</p>
                    </div>
                    <ul class="space-y-3 text-sm text-[#1F2937] dark:text-slate-200">
                        <li class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2"><i class="las la-check-circle text-[#10B981] text-lg"></i> MTN <span class="text-xs text-slate-500">(20s)</span></span>
                            <span class="font-bold">₦26.25</span>
                        </li>
                        <li class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2"><i class="las la-check-circle text-[#10B981] text-lg"></i> Airtel <span class="text-xs text-slate-500">(20s)</span></span>
                            <span class="font-bold">₦17.12</span>
                        </li>
                        <li class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2"><i class="las la-check-circle text-[#10B981] text-lg"></i> Glo <span class="text-xs text-slate-500">(120s)</span></span>
                            <span class="font-bold">₦42.00</span>
                        </li>
                        <li class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2"><i class="las la-check-circle text-[#10B981] text-lg"></i> T2 / 9mobile <span class="text-xs text-slate-500">(20s)</span></span>
                            <span class="font-bold">₦15.75</span>
                        </li>
                        <li class="flex items-center gap-2 pt-2 border-t border-[#D1D5DB]/50 dark:border-white/10 text-xs text-slate-600 dark:text-slate-300">
                            <i class="las la-tools text-[#0878F8] text-base"></i> Maintenance: ₦159,500 / month
                        </li>
                        <li class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                            <i class="las la-code text-[#0878F8] text-base"></i> API Forwarding &amp; Team Access
                        </li>
                    </ul>
                </div>
                <a href="{{ route('register') }}"
                    class="mt-8 block w-full rounded-full bg-[#0878F8] py-2.5 text-center text-sm font-semibold text-white shadow-md shadow-[#0878F8]/30 hover:bg-[#0B3A75] transition-all">
                    Choose Plus Plan
                </a>
            </div>

            <!-- Premium Plan -->
            <div class="glass-card rounded-3xl p-6 flex flex-col justify-between transition-all hover:-translate-y-1">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#6D5EF7] dark:text-purple-300">Growth Tier</span>
                        <span class="rounded-full bg-[#6D5EF7]/15 px-3 py-1 text-xs font-bold text-[#6D5EF7] dark:text-purple-300">
                            ₦159,500 / mo Maint.
                        </span>
                    </div>
                    <h3 class="text-2xl font-bold text-[#0B3A75] dark:text-white">Premium Plan</h3>
                    <div class="my-4 pb-4 border-b border-[#D1D5DB]/70 dark:border-white/10">
                        <p class="text-2xl font-extrabold text-[#0878F8] dark:text-[#22C7F8]">₦600,000 – ₦1,999,999</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Monthly session spend tier</p>
                    </div>
                    <ul class="space-y-3 text-sm text-[#1F2937] dark:text-slate-200">
                        <li class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2"><i class="las la-check-circle text-[#10B981] text-lg"></i> MTN <span class="text-xs text-slate-500">(20s)</span></span>
                            <span class="font-bold">₦21.75</span>
                        </li>
                        <li class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2"><i class="las la-check-circle text-[#10B981] text-lg"></i> Airtel <span class="text-xs text-slate-500">(20s)</span></span>
                            <span class="font-bold">₦14.18</span>
                        </li>
                        <li class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2"><i class="las la-check-circle text-[#10B981] text-lg"></i> Glo <span class="text-xs text-slate-500">(120s)</span></span>
                            <span class="font-bold">₦34.80</span>
                        </li>
                        <li class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2"><i class="las la-check-circle text-[#10B981] text-lg"></i> T2 / 9mobile <span class="text-xs text-slate-500">(20s)</span></span>
                            <span class="font-bold">₦13.05</span>
                        </li>
                        <li class="flex items-center gap-2 pt-2 border-t border-[#D1D5DB]/50 dark:border-white/10 text-xs text-slate-600 dark:text-slate-300">
                            <i class="las la-tools text-[#0878F8] text-base"></i> Maintenance: ₦159,500 / month
                        </li>
                        <li class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                            <i class="las la-headset text-[#0878F8] text-base"></i> Dedicated Routing &amp; Priority SLA
                        </li>
                    </ul>
                </div>
                <a href="{{ route('register') }}"
                    class="mt-8 block w-full rounded-full border border-[#0878F8]/50 py-2.5 text-center text-sm font-semibold text-[#0878F8] dark:text-[#22C7F8] hover:bg-[#0878F8] hover:text-white transition-all">
                    Select Premium
                </a>
            </div>

            <!-- Max Plan -->
            <div class="glass-card rounded-3xl p-6 flex flex-col justify-between transition-all hover:-translate-y-1">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#F59E0B]">Enterprise</span>
                        <span class="rounded-full bg-[#F59E0B]/15 px-3 py-1 text-xs font-bold text-[#F59E0B]">
                            ₦159,500 / mo Maint.
                        </span>
                    </div>
                    <h3 class="text-2xl font-bold text-[#0B3A75] dark:text-white">Max Plan</h3>
                    <div class="my-4 pb-4 border-b border-[#D1D5DB]/70 dark:border-white/10">
                        <p class="text-2xl font-extrabold text-[#0878F8] dark:text-[#22C7F8]">₦2,000,000+</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Monthly session spend tier</p>
                    </div>
                    <ul class="space-y-3 text-sm text-[#1F2937] dark:text-slate-200">
                        <li class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2"><i class="las la-check-circle text-[#10B981] text-lg"></i> MTN <span class="text-xs text-slate-500">(20s)</span></span>
                            <span class="font-bold">₦18.75</span>
                        </li>
                        <li class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2"><i class="las la-check-circle text-[#10B981] text-lg"></i> Airtel <span class="text-xs text-slate-500">(20s)</span></span>
                            <span class="font-bold">₦12.23</span>
                        </li>
                        <li class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2"><i class="las la-check-circle text-[#10B981] text-lg"></i> Glo <span class="text-xs text-slate-500">(120s)</span></span>
                            <span class="font-bold">₦30.00</span>
                        </li>
                        <li class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2"><i class="las la-check-circle text-[#10B981] text-lg"></i> T2 / 9mobile <span class="text-xs text-slate-500">(20s)</span></span>
                            <span class="font-bold">₦11.25</span>
                        </li>
                        <li class="flex items-center justify-between gap-2 pt-2 border-t border-[#D1D5DB]/50 dark:border-white/10 text-xs text-slate-600 dark:text-slate-300">
                            <span class="flex items-center gap-2"><i class="las la-tools text-[#0878F8] text-base"></i> Maintenance Fee</span>
                            <span class="font-semibold">₦159,500 / mo</span>
                        </li>
                        <li class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                            <i class="las la-user-tie text-[#0878F8] text-base"></i> Dedicated Account Manager
                        </li>
                    </ul>
                </div>
                <a href="{{ route('register') }}"
                    class="mt-8 block w-full rounded-full border border-[#0878F8]/50 py-2.5 text-center text-sm font-semibold text-[#0878F8] dark:text-[#22C7F8] hover:bg-[#0878F8] hover:text-white transition-all">
                    Go Max Enterprise
                </a>
            </div>
        </div>

        <div class="glass-pill mt-6 rounded-2xl px-6 py-4 text-center text-xs sm:text-sm text-[#1F2937] dark:text-slate-200">
            <strong>Plan Terms &amp; Maintenance:</strong> Active USSD plans include a monthly channel maintenance fee of <strong>₦159,500</strong> (excluding VAT). MTN, Airtel, and T2 (9mobile) sessions are charged per 20-second interval; Glo sessions are charged per 120-second interval. Bulk SMS and Airtime remain billed at standard tier rates.
        </div>
    </section>

    <!-- Why Choose Q4 Section -->
    <section id="why-q4" class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-[#0B3A75] dark:text-white">
                Why Businesses Trust Q4 International
            </h2>
            <p class="mt-3 text-slate-600 dark:text-slate-300">
                Engineered for high reliability, cryptographic security, and responsive local engineering support.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="glass-card rounded-3xl p-6 text-center space-y-4">
                <img src="{{ asset('assets/q4/security.svg') }}" alt="Security" class="mx-auto h-28 w-auto object-contain" />
                <h3 class="text-xl font-bold text-[#0B3A75] dark:text-white">Bank-Grade Security</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    End-to-end TLS encryption, Authenticator 2FA protection, and isolated API keys for every client organization.
                </p>
            </div>

            <div class="glass-card rounded-3xl p-6 text-center space-y-4">
                <img src="{{ asset('assets/q4/reliability.svg') }}" alt="Reliability" class="mx-auto h-28 w-auto object-contain" />
                <h3 class="text-xl font-bold text-[#0B3A75] dark:text-white">Proven Reliability</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Automated session recovery and direct Nigerian telco carrier gateways (MTN, Airtel, Glo, T2) built for sustained traffic spikes.
                </p>
            </div>

            <div class="glass-card rounded-3xl p-6 text-center space-y-4">
                <img src="{{ asset('assets/q4/support.svg') }}" alt="Support" class="mx-auto h-28 w-auto object-contain" />
                <h3 class="text-xl font-bold text-[#0B3A75] dark:text-white">24/7 Dedicated Support</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Fast engineering onboarding for custom USSD trees, external REST integrations, and telco sender ID registrations.
                </p>
            </div>
        </div>
    </section>

    <!-- FAQ Accordion -->
    <section id="faq" class="mx-auto max-w-4xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="text-center mb-10">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-[#0B3A75] dark:text-white">
                Frequently Asked Questions
            </h2>
        </div>

        @php
            $faqs = [
                [
                    'q' => 'What services does Q4 International provide?',
                    'a' => 'Q4 International provides programmable USSD shortcodes and Text/Visual menu builders, Bulk SMS, Airtime disbursement, Social Betting & Financial Engine infrastructure (lotto.q4iltd.com), PoS & hardware systems, Secure Hosting, Digital Payments, and Custom CRM Automation.'
                ],
                [
                    'q' => 'How are USSD sessions billed across Nigerian networks?',
                    'a' => 'MTN, Airtel, and T2 (9mobile) USSD sessions are billed per 20-second interval, while Glo sessions are billed per 120-second interval. Per-interval rates depend on your plan (Basic, Plus, Premium, or Max), plus a ₦159,500 monthly maintenance fee for active USSD channels.'
                ],
                [
                    'q' => 'Can I build a complex USSD menu quickly or connect my own backend API?',
                    'a' => 'Yes. Use our Text-to-Menu Builder to generate full nested trees from plain indented text (like lottery/betting flows) in seconds, or connect external REST endpoints via API nodes.'
                ],
                [
                    'q' => 'How do I fund my Q4 wallet balance?',
                    'a' => 'Sign in to your portal and open Billing & Wallet to fund your account balance online via Paystack or Squad.'
                ],
                [
                    'q' => 'Which mobile networks are supported?',
                    'a' => 'We support MTN, Airtel, Glo, and T2 (9mobile) across USSD menus, Bulk SMS delivery, and instant Airtime top-ups.'
                ],
                [
                    'q' => 'How long does a USSD session stay active on mobile networks?',
                    'a' => 'Standard Nigerian telco USSD sessions remain open for up to 120 seconds total (with a 160-character screen limit per menu prompt).'
                ],
                [
                    'q' => 'Can I upgrade my pricing tier anytime?',
                    'a' => 'Yes. Contact Q4 International support or your portal administrator to move between Basic, Plus, Premium, and Max tiers as your monthly session volume grows.'
                ],
            ];
        @endphp

        <div class="space-y-3" id="q4-faq-list">
            @foreach ($faqs as $index => $faq)
                <div class="glass-card rounded-2xl overflow-hidden">
                    <button type="button"
                        class="faq-toggle flex w-full items-center justify-between gap-4 px-6 py-4 text-left font-semibold text-[#0B3A75] dark:text-white"
                        aria-expanded="{{ $index === 0 ? 'true' : 'false' }}">
                        <span>{{ $faq['q'] }}</span>
                        <i class="las {{ $index === 0 ? 'la-minus' : 'la-plus' }} text-xl text-[#0878F8] dark:text-[#22C7F8] shrink-0"></i>
                    </button>
                    <div class="faq-body px-6 pb-5 text-sm text-slate-600 dark:text-slate-300 {{ $index === 0 ? '' : 'hidden' }}">
                        {{ $faq['a'] }}
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <!-- Deep Navy Glass Footer (#0B3A75) with Official Q4 Social Media Links -->
    <footer class="mt-10 bg-[#0B3A75]/95 backdrop-blur-xl text-white border-t border-[#22C7F8]/20">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 items-start">
                <div class="space-y-4">
                    <img src="{{ asset('assets/images/q4-logo-white.png') }}" alt="Q4 International"
                        class="h-11 w-auto object-contain" />
                    <p class="text-sm text-blue-100/85 max-w-xs leading-relaxed">
                        Building the Digital Infrastructure of Tomorrow — Enterprise USSD, Messaging, Digital Payments &amp; Social Betting Engine by Q4I Limited.
                    </p>
                </div>

                <div class="space-y-3">
                    <h4 class="text-base font-bold text-white">Comms Platform</h4>
                    <ul class="space-y-2 text-sm text-blue-100/85">
                        <li><a href="#services" class="hover:text-[#22C7F8]">USSD &amp; Messaging</a></li>
                        <li><a href="#pricing" class="hover:text-[#22C7F8]">Pricing Plans (Basic / Plus / Premium / Max)</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-[#22C7F8]">Client &amp; Admin Sign In</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-[#22C7F8]">Create Account</a></li>
                    </ul>
                </div>

                <div class="space-y-3">
                    <h4 class="text-base font-bold text-white">Q4 Ecosystem</h4>
                    <ul class="space-y-2 text-sm text-blue-100/85">
                        <li><a href="https://q4iltd.com" target="_blank" rel="noopener noreferrer" class="hover:text-[#22C7F8]">Q4I Main Site (q4iltd.com)</a></li>
                        <li><a href="https://lotto.q4iltd.com" target="_blank" rel="noopener noreferrer" class="hover:text-[#22C7F8]">Social Betting (lotto.q4iltd.com)</a></li>
                        <li><a href="https://host.q4iltd.com/" target="_blank" rel="noopener noreferrer" class="hover:text-[#22C7F8]">Secure Hosting</a></li>
                        <li><a href="https://pay.q4iltd.com/merchant/login" target="_blank" rel="noopener noreferrer" class="hover:text-[#22C7F8]">Digital Payments</a></li>
                        <li><a href="https://crm.q4iltd.com/authentication/login" target="_blank" rel="noopener noreferrer" class="hover:text-[#22C7F8]">Custom CRM Automation</a></li>
                    </ul>
                </div>

                <div class="space-y-3">
                    <h4 class="text-base font-bold text-white">Connect &amp; Support</h4>
                    <p class="text-xs text-blue-100/85 leading-relaxed">No 10A Dolo Oyekan Street, Lekki Phase 1, Lagos, 106104</p>
                    <p class="text-sm text-blue-100/90">Email: support@q4iltd.com</p>
                    <p class="text-sm text-blue-100/90">Phone: +234 904 593 6806</p>
                    <div class="flex flex-wrap items-center gap-2.5 pt-2">
                        <a href="https://www.facebook.com/share/17V6EsfSdJ/" target="_blank" rel="noopener noreferrer" aria-label="Facebook" title="Facebook"
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 hover:bg-[#0878F8] transition-colors">
                            <i class="lab la-facebook-f text-lg"></i>
                        </a>
                        <a href="https://www.instagram.com/q4international?igsh=MXh1N3BneXZiODhlMQ==" target="_blank" rel="noopener noreferrer" aria-label="Instagram" title="Instagram"
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 hover:bg-[#0878F8] transition-colors">
                            <i class="lab la-instagram text-lg"></i>
                        </a>
                        <a href="https://www.linkedin.com/company/q4i-limited/" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn" title="LinkedIn"
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 hover:bg-[#0878F8] transition-colors">
                            <i class="lab la-linkedin-in text-lg"></i>
                        </a>
                        <a href="https://www.threads.com/@q4international" target="_blank" rel="noopener noreferrer" aria-label="Threads" title="Threads"
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 hover:bg-[#0878F8] transition-colors">
                            <i class="las la-hashtag text-lg"></i>
                        </a>
                        <a href="https://www.tiktok.com/@q4international?_r=1&_t=ZS-924eppm1mSe" target="_blank" rel="noopener noreferrer" aria-label="TikTok" title="TikTok"
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 hover:bg-[#0878F8] transition-colors">
                            <i class="las la-music text-lg"></i>
                        </a>
                        <a href="https://wa.me/2349045936806" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp" title="WhatsApp"
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-[#10B981]/80 hover:bg-[#10B981] transition-colors">
                            <i class="lab la-whatsapp text-lg"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="mt-10 border-t border-white/15 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-blue-100/80">
                <p>&copy; {{ date('Y') }} Q4I Limited (Q4 International). All rights reserved.</p>
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
