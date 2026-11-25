<!DOCTYPE html>
<html dir="ltr" lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon" />
    @vite(['resources/css/app.css'])
    <title>Sign Up — {{ config('app.name') }}</title>
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
                    <form method="POST" action="{{ route('register.submit') }}" class="col-span-12 lg:col-span-7">
                        @csrf
                        <div class="box bg-primary/5 lg:p-6 xl:p-8 border border-n30">
                            <h3 class="h3 mb-3">Create Client Account</h3>
                            <p class="md:mb-6 md:pb-6 mb-4 pb-4 bb-dashed text-sm md:text-base text-n200">
                                Get instant access to USSD Menu Builder, Bulk SMS, Airtime &amp; API Keys
                            </p>

                            @include('partials._alerts')

                            <div class="mb-4">
                                <label for="name" class="md:text-lg font-medium block mb-2">Company / Account Name</label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                    class="w-full text-sm bg-n0 border border-n30 rounded-3xl px-4 md:px-6 py-3"
                                    placeholder="Acme Communications Ltd" />
                            </div>

                            <div class="mb-4">
                                <label for="email" class="md:text-lg font-medium block mb-2">Email Address</label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                    class="w-full text-sm bg-n0 border border-n30 rounded-3xl px-4 md:px-6 py-3"
                                    placeholder="you@company.com" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="password" class="md:text-lg font-medium block mb-2">Password</label>
                                    <input type="password" name="password" id="password" required
                                        class="w-full text-sm bg-n0 border border-n30 rounded-3xl px-4 md:px-6 py-3"
                                        placeholder="Min. 8 characters" />
                                </div>
                                <div>
                                    <label for="password_confirmation" class="md:text-lg font-medium block mb-2">Confirm Password</label>
                                    <input type="password" name="password_confirmation" id="password_confirmation" required
                                        class="w-full text-sm bg-n0 border border-n30 rounded-3xl px-4 md:px-6 py-3"
                                        placeholder="Repeat Password" />
                                </div>
                            </div>

                            <p class="text-sm mt-3">
                                Already have an account?
                                <a class="text-primary font-medium" href="{{ route('login') }}">Sign In</a>
                            </p>

                            <div class="mt-8">
                                <button type="submit" class="btn-primary px-8">Create Account</button>
                            </div>
                        </div>
                    </form>
                    <div class="col-span-12 lg:col-span-5 hidden lg:flex justify-center">
                        <img src="{{ asset('assets/images/auth.png') }}" alt="Signup illustration" class="max-w-full h-auto" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
