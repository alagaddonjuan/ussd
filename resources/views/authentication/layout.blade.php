<!DOCTYPE html>
<html dir="ltr" lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon" />
    @vite(['resources/css/app.css'])
    <title>@yield('title', config('app.name') . ' — Q4 International')</title>
    <style>
        .auth-dot-grid {
            background-image: radial-gradient(rgba(11, 58, 117, 0.14) 1px, transparent 1px);
            background-size: 24px 24px;
        }
        .dark .auth-dot-grid {
            background-image: radial-gradient(rgba(34, 199, 248, 0.14) 1px, transparent 1px);
        }
        .auth-frosted-panel {
            background: rgba(255, 255, 255, 0.66);
            backdrop-filter: blur(26px) saturate(180%);
            -webkit-backdrop-filter: blur(26px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.85);
            box-shadow:
                0 28px 70px -18px rgba(11, 58, 117, 0.28),
                0 0 0 1px rgba(8, 120, 248, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.95);
        }
        .dark .auth-frosted-panel {
            background: rgba(9, 21, 42, 0.72);
            border: 1px solid rgba(34, 199, 248, 0.22);
            box-shadow:
                0 28px 70px -18px rgba(0, 0, 0, 0.65),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        .auth-glass-input {
            background: rgba(255, 255, 255, 0.72);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(11, 58, 117, 0.15);
            transition: all 0.2s ease;
        }
        .auth-glass-input:focus {
            background: rgba(255, 255, 255, 0.96);
            border-color: #0878F8 !important;
            box-shadow: 0 0 0 4px rgba(8, 120, 248, 0.16);
        }
        .dark .auth-glass-input {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(34, 199, 248, 0.2);
            color: #F8FAFC;
        }
        .dark .auth-glass-input:focus {
            background: rgba(15, 34, 66, 0.88);
        }
    </style>
</head>

<body class="min-h-screen antialiased text-[#1F2937] dark:text-slate-100 relative overflow-x-hidden">
    <!-- Rich Q4I Ambient Refraction Backdrop -->
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden auth-dot-grid">
        <div class="absolute -top-24 -left-20 h-[28rem] w-[28rem] rounded-full bg-[#0878F8]/30 blur-3xl"></div>
        <div class="absolute top-1/4 -right-24 h-[30rem] w-[30rem] rounded-full bg-[#22C7F8]/28 blur-3xl"></div>
        <div class="absolute -bottom-24 left-1/3 h-[26rem] w-[26rem] rounded-full bg-[#6D5EF7]/25 blur-3xl"></div>
    </div>

    <!-- Floating Frosted Header Bar -->
    <header class="mx-auto max-w-6xl px-4 pt-5 sm:px-6">
        <div class="glass-pill flex items-center justify-between rounded-full px-4 py-2.5 sm:px-6 shadow-sm">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <img src="{{ asset('assets/images/q4-logo.png') }}" alt="Q4 International"
                    class="h-9 w-auto object-contain dark:hidden" />
                <img src="{{ asset('assets/images/q4-logo-white.png') }}" alt="Q4 International"
                    class="hidden h-9 w-auto object-contain dark:block" />
            </a>
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}"
                    class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-semibold text-[#0B3A75] dark:text-[#22C7F8] hover:bg-[#0878F8]/10 transition-colors">
                    <i class="las la-arrow-left"></i>
                    <span>Back to Home</span>
                </a>
                @yield('header-action')
            </div>
        </div>
    </header>

    <main class="flex min-h-[calc(100vh-130px)] items-center justify-center px-4 py-8 sm:px-6">
        @yield('auth-content')
    </main>

    <footer class="pb-6 text-center text-xs text-slate-500 dark:text-slate-400">
        &copy; {{ date('Y') }} Q4I Limited (Q4 International) &bull; support@q4iltd.com
    </footer>

    <script>
        function toggleAuthPassword(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            const icon = btn?.querySelector('i');
            if (icon) {
                icon.classList.toggle('la-eye', showing);
                icon.classList.toggle('la-eye-slash', !showing);
            }
        }
    </script>
    @include('partials._antigravity-fx', ['tilt' => false])
</body>

</html>
