<!DOCTYPE html>
<html dir="ltr" lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon" />
    @vite(['resources/css/app.css'])
    <title>Reset Password — {{ config('app.name') }}</title>
</head>

<body class="vertical bg-secondary/5">
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-10">
        <a href="{{ route('home') }}" class="mb-6 inline-flex items-center">
            <img src="{{ asset('assets/images/q4-logo.png') }}" alt="Q4 International" class="h-12 w-auto object-contain" />
        </a>
        <div class="box max-w-[480px] w-full p-6 md:p-8">
            <h3 class="h3 mb-2">Choose New Password</h3>
            <p class="text-sm text-n200 mb-6 pb-4 bb-dashed">
                Enter your new account password below.
            </p>

            @include('partials._alerts')

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}" />
                <div>
                    <label for="password" class="block font-medium mb-2">New Password</label>
                    <input type="password" name="password" id="password" required
                        class="w-full text-sm bg-primary/5 border border-n30 rounded-3xl px-4 py-3"
                        placeholder="Min. 8 characters" />
                </div>
                <div>
                    <label for="password_confirmation" class="block font-medium mb-2">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                        class="w-full text-sm bg-primary/5 border border-n30 rounded-3xl px-4 py-3"
                        placeholder="Repeat new password" />
                </div>
                <button type="submit" class="btn-primary w-full justify-center">Update Password</button>
            </form>
        </div>
    </div>
</body>

</html>
