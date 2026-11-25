<!DOCTYPE html>
<html dir="ltr" lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon" />
    @vite(['resources/css/app.css'])
    <title>Sign In — {{ config('app.name') }}</title>
</head>

<body class="vertical bg-secondary/5">
    <div class="relative min-h-screen bg-secondary/5">
        <img src="{{ asset('assets/images/ellipse1.png') }}" class="absolute top-16 md:top-5 ltr:right-10 rtl:left-10" alt="ellipse" />
        <img src="{{ asset('assets/images/ellipse2.png') }}" class="absolute bottom-6 ltr:left-0 rtl:right-0 ltr:sm:left-32 rtl:sm:right-32" alt="ellipse" />

        <a href="{{ route('home') }}" class="inline-block p-6 lg:p-8 relative z-10">
            <img src="{{ asset('assets/images/logo-with-text-dark.png') }}" alt="{{ config('app.name') }}" class="h-9" />
        </a>

        <div class="flex items-center justify-center mt-4">
            <div class="relative z-10 max-w-[1200px] w-full mx-auto px-3 pb-10">
                <div class="box p-4 md:p-6 xl:p-8 grid grid-cols-12 gap-6 items-center">
                    <form method="POST" action="{{ route('login.submit') }}" class="col-span-12 lg:col-span-7">
                        @csrf
                        <div class="box bg-primary/5 lg:p-6 xl:p-8 border border-n30">
                            <h3 class="h3 mb-3">Welcome Back!</h3>
                            <p class="md:mb-6 md:pb-6 mb-4 pb-4 bb-dashed text-sm md:text-base text-n200">
                                Sign in to manage your USSD menus, SMS, and wallet balance
                            </p>

                            @include('partials._alerts')

                            <div class="mb-4">
                                <label for="email" class="md:text-lg font-medium block mb-2">Email Address</label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                                    class="w-full text-sm bg-n0 border border-n30 rounded-3xl px-4 md:px-6 py-3"
                                    placeholder="you@company.com" />
                            </div>

                            <div class="mb-4">
                                <label for="password" class="md:text-lg font-medium block mb-2">Password</label>
                                <div class="bg-n0 border border-n30 rounded-3xl px-4 md:px-6 py-2.5 relative">
                                    <input type="password" name="password" id="password" required
                                        class="w-11/12 text-sm bg-transparent p-0 border-none"
                                        placeholder="Enter Password" />
                                    <button type="button" onclick="const i=document.getElementById('password'); i.type=i.type==='password'?'text':'password';"
                                        class="absolute ltr:right-5 rtl:left-5 top-1/2 -translate-y-1/2 cursor-pointer text-n100">
                                        <i class="las la-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-sm mt-3">
                                <p>
                                    Don&apos;t have an account?
                                    <a class="text-primary font-medium" href="{{ route('register') }}">Sign Up</a>
                                </p>
                                <a href="{{ route('password.request') }}" class="text-primary font-medium">
                                    Forgot Password?
                                </a>
                            </div>

                            <div class="mt-8">
                                <button type="submit" class="btn-primary px-8">Sign In</button>
                            </div>
                        </div>
                    </form>
                    <div class="col-span-12 lg:col-span-5 hidden lg:flex justify-center">
                        <img src="{{ asset('assets/images/auth.png') }}" alt="Authentication illustration" class="max-w-full h-auto" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
