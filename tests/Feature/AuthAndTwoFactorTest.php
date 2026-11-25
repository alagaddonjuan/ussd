<?php

namespace Tests\Feature;

use App\Mail\PasswordResetMail;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AuthAndTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_register_and_gets_default_api_key(): void
    {
        $res = $this->post('/register', [
            'name' => 'Test Telecom',
            'email' => 'client@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $res->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('clients', ['email' => 'client@example.com', 'status' => 'active']);
        $this->assertDatabaseCount('api_keys', 1);
    }

    public function test_login_supports_nodejs_bcryptjs_2a_hashes_and_admin_redirect(): void
    {
        // Real Node.js bcryptjs $2a$10$ hash for "password"
        $bcryptJsHash = '$2a$10$lrtcv10uYP1iBNX1Gtkmae1B8sPxLfINwugzQZ8dO23Je10M/cNy6';

        Client::create([
            'name' => 'Admin Account',
            'email' => 'admin@example.com',
            'password' => $bcryptJsHash,
            'is_admin' => true,
            'status' => 'active',
        ]);

        $res = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $res->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();
    }

    public function test_two_factor_challenge_and_password_reset(): void
    {
        Mail::fake();
        $g2fa = new Google2FA();
        $secret = $g2fa->generateSecretKey();

        $client = Client::create([
            'name' => 'Secure Client',
            'email' => 'secure@example.com',
            'password' => Hash::make('OldPass123!'),
            'is_2fa_enabled' => true,
            'two_fa_secret' => $secret,
            'status' => 'active',
        ]);

        $res = $this->post('/login', [
            'email' => 'secure@example.com',
            'password' => 'OldPass123!',
        ]);

        $res->assertRedirect('/auth/2fa');
        $this->assertGuest();

        $code = $g2fa->getCurrentOtp($secret);
        $verify = $this->post('/auth/2fa', ['code' => $code]);
        $verify->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($client);

        // Forgot & reset password
        $this->post('/logout');
        $this->post('/forgot-password', ['email' => 'secure@example.com'])->assertSessionHas('status');

        Mail::assertSent(PasswordResetMail::class);
        $client->refresh();
        $this->assertNotNull($client->password_reset_token);

        $reset = $this->post('/reset-password', [
            'token' => $client->password_reset_token,
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ]);
        $reset->assertRedirect('/login');
        $client->refresh();
        $this->assertTrue(Hash::check('NewPass123!', $client->password));
    }

    public function test_admin_legacy_52_char_speakeasy_2fa_secret_verifies_without_500(): void
    {
        $legacySecret = 'LBKVWNSQKMSGO22XHZ2XWNRRI5UGI2DUK5KCKY3UNUZDSPTEGRWQ';
        $g2fa = new Google2FA();
        $g2fa->setEnforceGoogleAuthenticatorCompatibility(false);

        $admin = Client::create([
            'name' => 'Q4 Admin',
            'email' => 'admin2fa@q4iltd.com',
            'password' => Hash::make('AdminPass123!'),
            'is_admin' => true,
            'is_2fa_enabled' => true,
            'two_fa_secret' => $legacySecret,
            'status' => 'active',
        ]);

        $loginRes = $this->post('/login', [
            'email' => 'admin2fa@q4iltd.com',
            'password' => 'AdminPass123!',
        ]);
        $loginRes->assertRedirect('/auth/2fa');
        $this->assertGuest();

        $this->get('/auth/2fa')->assertOk()->assertSee('q4-logo.png');

        $otp = $g2fa->getCurrentOtp($legacySecret);
        $verifyRes = $this->post('/auth/2fa', ['code' => $otp]);
        $verifyRes->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($admin);
    }
}
