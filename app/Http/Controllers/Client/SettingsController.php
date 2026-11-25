<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function profile(Request $request): View
    {
        return view('client.settings-profile', ['client' => $request->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $client = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sender_id' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $updates = [
            'name' => $data['name'],
            'sender_id' => $data['sender_id'] ?? null,
        ];

        if (! empty($data['password'])) {
            $updates['password'] = Hash::make($data['password']);
        }

        $client->update($updates);

        return back()->with('status', 'Profile settings updated.');
    }

    public function security(Request $request, TwoFactorService $twoFactor): View
    {
        $client = $request->user();
        $pendingSecret = $request->session()->get('2fa_setup_secret');
        $qrSvg = $pendingSecret ? $twoFactor->getQrCodeSvg($client->email, $pendingSecret) : null;

        return view('client.settings-security', compact('client', 'pendingSecret', 'qrSvg'));
    }

    public function generateTwoFactor(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $secret = $twoFactor->generateSecret();
        $request->session()->put('2fa_setup_secret', $secret);

        return redirect()->route('settings.security')
            ->with('status', 'Scan the QR code below with your authenticator app, then enter the 6-digit code to activate 2FA.');
    }

    public function enableTwoFactor(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'min:6', 'max:10'],
        ]);

        $secret = (string) $request->session()->get('2fa_setup_secret', '');
        if ($secret === '' || ! $twoFactor->verifyCode($secret, $data['code'])) {
            return back()->withErrors(['code' => 'Invalid 6-digit authenticator code.']);
        }

        $request->user()->update([
            'two_fa_secret' => $secret,
            'is_2fa_enabled' => true,
        ]);

        $request->session()->forget('2fa_setup_secret');

        return redirect()->route('settings.security')->with('status', 'Two-factor authentication is now enabled.');
    }

    public function disableTwoFactor(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $client = $request->user();

        $data = $request->validate([
            'code' => ['required', 'string', 'min:6', 'max:10'],
        ]);

        if (! $client->two_fa_secret || ! $twoFactor->verifyCode($client->two_fa_secret, $data['code'])) {
            return back()->withErrors(['disable_code' => 'Invalid 2FA code. Could not disable 2FA.']);
        }

        $client->update([
            'two_fa_secret' => null,
            'is_2fa_enabled' => false,
        ]);

        return redirect()->route('settings.security')->with('status', 'Two-factor authentication has been disabled.');
    }
}
