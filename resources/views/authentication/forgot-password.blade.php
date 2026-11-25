<!DOCTYPE html>
<html dir="ltr" lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon" />
    @vite(['resources/css/app.css'])
    <title>Forgot Password — {{ config('app.name') }}</title>
</head>

<body class="vertical bg-secondary/5">
    <div class="min-h-screen flex items-center justify-center px-4 py-10">
        <div class="box max-w-[480px] w-full p-6 md:p-8 border border-n30">
            <h3 class="h3 mb-2">Forgot Password?</h3>
            <p class="text-sm text-n200 mb-6 pb-4 bb-dashed">
                Enter your registered email address and we will send you a password reset link.
            </p>

            @include('partials._alerts')

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block font-medium mb-2">Email Address</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                        class="w-full text-sm bg-primary/5 border border-n30 rounded-3xl px-4 py-3"
                        placeholder="you@company.com" />
                </div>
                <div class="flex items-center justify-between pt-2">
                    <a href="{{ route('login') }}" class="text-sm text-primary">Back to Sign In</a>
                    <button type="submit" class="btn-primary px-6">Send Reset Link</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
