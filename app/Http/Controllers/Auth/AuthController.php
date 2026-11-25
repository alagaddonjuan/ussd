<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\ApiKey;
use App\Models\Client;
use App\Models\PricingTier;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('authentication.signin');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $client = Client::query()->where('email', $credentials['email'])->first();

        if (! $client || ! $this->verifyClientPassword($client, $credentials['password'])) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Invalid email address or password.']);
        }

        if ($client->status === 'suspended') {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Your account has been suspended. Please contact support.']);
        }

        if ($client->is_2fa_enabled && ! empty($client->two_fa_secret)) {
            $request->session()->put('2fa_pending_client_id', $client->id);

            return redirect()->route('2fa.challenge');
        }

        Auth::login($client);
        $request->session()->regenerate();

        return redirect()->intended(
            $client->isAdmin() ? route('admin.dashboard') : route('dashboard')
        );
    }

    public function showTwoFactor(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('2fa_pending_client_id')) {
            return redirect()->route('login');
        }

        return view('authentication.two-factor');
    }

    public function verifyTwoFactor(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $clientId = $request->session()->get('2fa_pending_client_id');
        if (! $clientId) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'min:6', 'max:10'],
        ]);

        $client = Client::find($clientId);
        if (! $client || ! $client->two_fa_secret || ! $twoFactor->verifyCode($client->two_fa_secret, $data['code'])) {
            return back()->withErrors(['code' => 'Invalid 2FA verification code. Please try again.']);
        }

        $request->session()->forget('2fa_pending_client_id');
        Auth::login($client);
        $request->session()->regenerate();

        return redirect()->intended(
            $client->isAdmin() ? route('admin.dashboard') : route('dashboard')
        );
    }

    public function showRegister(): View
    {
        return view('authentication.signup');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:clients,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $defaultTier = PricingTier::query()->where('tier_name', 'Standard')->first()
            ?? PricingTier::query()->first();

        $client = DB::transaction(function () use ($data, $defaultTier): Client {
            $client = Client::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'token_balance' => 0,
                'status' => 'active',
                'is_admin' => false,
                'is_2fa_enabled' => false,
                'pricing_tier_id' => $defaultTier?->id,
                'created_at' => now(),
            ]);

            ApiKey::create([
                'client_id' => $client->id,
                'key_name' => 'Default Key',
                'api_key' => 'q4i_'.bin2hex(random_bytes(16)),
                'created_at' => now(),
            ]);

            return $client;
        });

        Auth::login($client);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('status', 'Account created successfully. Welcome to '.config('app.name').'!');
    }

    public function showForgotPassword(): View
    {
        return view('authentication.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $client = Client::query()->where('email', $data['email'])->first();

        if ($client) {
            $token = Str::random(40);
            $client->update([
                'password_reset_token' => $token,
                'password_reset_expires' => now()->addHour(),
            ]);

            $resetUrl = route('password.reset', ['token' => $token, 'email' => $client->email]);

            Mail::to($client->email)->send(new PasswordResetMail($client->name, $resetUrl));
        }

        return back()->with('status', 'If that email exists in our system, a password reset link has been sent.');
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('authentication.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $client = Client::query()
            ->where('password_reset_token', $data['token'])
            ->where('password_reset_expires', '>', now())
            ->first();

        if (! $client) {
            return back()->withErrors(['token' => 'This password reset link is invalid or has expired.']);
        }

        $client->update([
            'password' => Hash::make($data['password']),
            'password_reset_token' => null,
            'password_reset_expires' => null,
        ]);

        return redirect()->route('login')
            ->with('status', 'Your password has been updated. You can now sign in.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function verifyClientPassword(Client $client, string $plainPassword): bool
    {
        $storedHash = (string) $client->password;
        if ($storedHash === '') {
            return false;
        }

        // Node.js bcryptjs produces $2a$/$2b$ hashes; PHP's password_verify supports them,
        // whereas Laravel's BcryptHasher::check rejects non-$2y$ algorithm headers.
        if (str_starts_with($storedHash, '$2a$') || str_starts_with($storedHash, '$2b$')) {
            if (! password_verify($plainPassword, $storedHash)) {
                return false;
            }

            $client->update(['password' => Hash::make($plainPassword)]);

            return true;
        }

        return Hash::check($plainPassword, $storedHash);
    }
}
