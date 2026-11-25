<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Client;
use App\Models\PricingTier;
use App\Models\ServicePrice;
use App\Models\Transaction;
use App\Models\UssdMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BillingAndServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_paystack_webhook_validates_signature_and_is_idempotent(): void
    {
        config(['services.paystack.secret_key' => 'sk_test_secret_123']);

        $client = Client::create([
            'name' => 'Wallet Client',
            'email' => 'wallet@example.com',
            'password' => Hash::make('Password123!'),
            'token_balance' => 100,
            'status' => 'active',
        ]);

        Transaction::create([
            'client_id' => $client->id,
            'type' => 'credit',
            'reference' => 'REF-TEST-001',
            'amount' => 5000,
            'tokens_purchased' => 5000,
            'status' => 'Pending',
            'gateway' => 'paystack',
        ]);

        $payload = json_encode([
            'event' => 'charge.success',
            'data' => [
                'reference' => 'REF-TEST-001',
                'amount' => 500000,
                'metadata' => ['client_id' => $client->id],
            ],
        ]);

        // Reject invalid signature
        $this->postJson('/billing/webhook', json_decode($payload, true), [
            'x-paystack-signature' => 'invalid',
        ])->assertStatus(401);

        $sig = hash_hmac('sha512', $payload, 'sk_test_secret_123');

        // First delivery credits 5000
        $this->call('POST', '/billing/webhook', [], [], [], [
            'HTTP_X_PAYSTACK_SIGNATURE' => $sig,
            'CONTENT_TYPE' => 'application/json',
        ], $payload)->assertOk();

        $client->refresh();
        $this->assertEquals(5100, $client->token_balance);

        // Second delivery must NOT double-credit
        $this->call('POST', '/billing/webhook', [], [], [], [
            'HTTP_X_PAYSTACK_SIGNATURE' => $sig,
            'CONTENT_TYPE' => 'application/json',
        ], $payload)->assertOk();

        $client->refresh();
        $this->assertEquals(5100, $client->token_balance);
    }

    public function test_sms_and_airtime_deduct_balance_and_prevent_idor(): void
    {
        $tier = PricingTier::create(['tier_name' => 'Standard']);
        ServicePrice::create(['pricing_tier_id' => $tier->id, 'service_name' => 'SMS', 'price' => 2.50]);

        $alice = Client::create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => Hash::make('Password123!'),
            'token_balance' => 200,
            'status' => 'active',
            'pricing_tier_id' => $tier->id,
        ]);

        $bob = Client::create([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => Hash::make('Password123!'),
            'token_balance' => 200,
            'status' => 'active',
        ]);

        $bobMenu = UssdMenu::create([
            'client_id' => $bob->id,
            'menu_name' => 'Bob Menu',
            'is_active' => false,
            'is_archived' => false,
        ]);

        $bobKey = ApiKey::create([
            'client_id' => $bob->id,
            'key_name' => 'Bob Key',
            'api_key' => 'live_bob_123',
        ]);

        // Alice sends 1-page SMS (cost 2.50) and 100 NGN airtime
        $this->actingAs($alice)->post('/services/sms', [
            'to' => '+2348099990000',
            'message' => 'Hello world',
        ])->assertSessionHas('status');

        $this->actingAs($alice)->post('/services/airtime', [
            'phone_number' => '+2348099990000',
            'amount' => 100,
            'network' => 'MTN',
        ])->assertSessionHas('status');

        $alice->refresh();
        $this->assertEquals(97.50, $alice->token_balance);

        // Alice cannot mutate Bob's menu or API key (IDOR blocked)
        $this->actingAs($alice)->get("/ussd-menus/{$bobMenu->id}")->assertStatus(403);
        $this->actingAs($alice)->delete("/api-keys/{$bobKey->id}")->assertStatus(403);
    }
}
