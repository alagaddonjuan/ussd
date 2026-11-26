<div class="hidden lg:flex lg:col-span-5 flex-col justify-between bg-gradient-to-br from-[#0B3A75] via-[#0B3A75]/95 to-[#0878F8] p-8 xl:p-10 text-white relative overflow-hidden">
    <div class="pointer-events-none absolute -top-16 -right-16 h-56 w-56 rounded-full bg-[#22C7F8]/25 blur-2xl"></div>
    <div class="pointer-events-none absolute -bottom-16 -left-12 h-56 w-56 rounded-full bg-[#6D5EF7]/30 blur-2xl"></div>

    <div class="relative z-10 space-y-6">
        <div class="inline-flex items-center gap-2 rounded-full bg-white/12 px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-wider text-[#22C7F8] border border-white/15 backdrop-blur-md">
            <span class="inline-block h-2 w-2 rounded-full bg-[#10B981]"></span>
            <span>{{ $badge }}</span>
        </div>

        <div>
            <h2 class="text-2xl xl:text-3xl font-extrabold leading-tight text-white">
                {{ $heading }}
            </h2>
            <p class="mt-3 text-sm text-blue-100/85 leading-relaxed">
                {{ $subtext }}
            </p>
        </div>

        <!-- Mini Glass USSD Session Preview -->
        <div class="rounded-2xl bg-white/10 p-4 border border-white/20 backdrop-blur-md space-y-2.5 font-mono text-xs">
            <div class="flex items-center justify-between text-[#22C7F8]">
                <span>*384*23# &bull; Q4 Services</span>
                <span class="rounded-full bg-[#10B981]/25 px-2 py-0.5 text-[10px] text-emerald-200">99.98% Uptime</span>
            </div>
            <div class="rounded-xl bg-[#061022]/55 p-3 text-slate-100 leading-relaxed">
                CON Welcome to Q4 Services<br />
                1. Text-to-Menu Builder<br />
                2. Bulk SMS &amp; Airtime<br />
                3. Paystack Wallet &amp; APIs
            </div>
        </div>
    </div>

    <div class="relative z-10 space-y-3 pt-6 border-t border-white/15">
        <div class="flex items-center gap-3 text-xs text-blue-100/90">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/15 text-[#22C7F8]">
                <i class="las la-shield-alt text-base"></i>
            </span>
            <span>Authenticator 2FA &amp; IP-whitelisted API credentials</span>
        </div>
        <div class="flex items-center gap-3 text-xs text-blue-100/90">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/15 text-[#10B981]">
                <i class="las la-broadcast-tower text-base"></i>
            </span>
            <span>Direct carrier support across MTN, Airtel, Glo &amp; T2</span>
        </div>
    </div>
</div>
