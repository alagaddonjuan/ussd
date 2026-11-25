<footer class="footer glass-footer">
    <div class="flex flex-col items-center justify-center gap-3 px-4 py-5 lg:flex-row lg:justify-between xxl:px-8">
        <p class="text-sm max-md:w-full max-md:text-center lg:text-base">
            Copyright &copy; <span id="current-year">{{ date('Y') }}</span>
            <a class="text-primary font-medium" href="{{ route('home') }}">Q4 International</a>.
            All rights reserved.
        </p>
        <div class="flex gap-4 text-sm text-n100">
            <span>USSD Gateway: <code class="text-primary font-semibold">/ussd</code></span>
            <span>Paystack Webhook: <code class="text-primary font-semibold">/billing/webhook</code></span>
        </div>
    </div>
</footer>

@yield('page-modal')

<div id="customizer-container" class="z-[60] w-full"></div>

@stack('page-js')

@vite(['resources/js/app.js'])

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.body.classList.remove('hidden');
    });
</script>

</body>

</html>
