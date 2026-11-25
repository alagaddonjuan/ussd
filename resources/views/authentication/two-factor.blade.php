<!DOCTYPE html>
<html dir="ltr" lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon" />
    @vite(['resources/css/app.css'])
    <title>2FA Verification — {{ config('app.name') }}</title>
</head>

<body class="vertical bg-secondary/5">
    <div class="min-h-screen flex items-center justify-center px-4 py-10">
        <div class="box max-w-[480px] w-full p-6 md:p-8 border border-n30">
            <h3 class="h3 mb-2">Two-Factor Authentication</h3>
            <p class="text-sm text-n200 mb-6 pb-4 bb-dashed">
                Enter the 6-digit code from your authenticator app to complete login.
            </p>

            @include('partials._alerts')

            <form method="POST" action="{{ route('2fa.verify') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="code" class="block font-medium mb-2">6-Digit Verification Code</label>
                    <input type="text" name="code" id="code" inputmode="numeric" maxlength="10" required autofocus
                        class="w-full text-center tracking-widest text-lg bg-primary/5 border border-n30 rounded-3xl px-4 py-3"
                        placeholder="000000" />
                </div>
                <div class="flex items-center justify-between pt-2">
                    <a href="{{ route('login') }}" class="text-sm text-primary">Back to Login</a>
                    <button type="submit" class="btn-primary px-6">Verify Code</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
