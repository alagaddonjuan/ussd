<?php

namespace Tests\Feature;

use App\Mail\LowBalanceAlertMail;
use App\Mail\MaintenanceReminderMail;
use App\Models\ApiKey;
use App\Models\Client;
use App\Models\PricingTier;
use App\Models\ServicePrice;
use App\Models\Transaction;
use App\Models\UssdMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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

    public function test_low_balance_email_alert_triggers_once_within_24h(): void
    {
        Mail::fake();
        $tier = PricingTier::create(['tier_name' => 'Standard']);
        ServicePrice::create(['pricing_tier_id' => $tier->id, 'service_name' => 'SMS', 'price' => 10.00]);

        $client = Client::create([
            'name' => 'Low Wallet Client',
            'email' => 'alerts@q4iltd.com',
            'password' => Hash::make('Password123!'),
            'token_balance' => 105.00,
            'low_balance_threshold' => 100.00,
            'status' => 'active',
            'pricing_tier_id' => $tier->id,
        ]);

        // Drops balance from 105 to 95 (<= 100 threshold) -> triggers alert email once
        $this->actingAs($client)->post('/services/sms', [
            'to' => '+2348099990001',
            'message' => 'Alert threshold trigger test',
        ])->assertSessionHas('status');

        Mail::assertSentTimes(LowBalanceAlertMail::class, 1);

        // Second SMS within 24h should NOT resend duplicate alert email
        $this->actingAs($client)->post('/services/sms', [
            'to' => '+2348099990001',
            'message' => 'Second SMS within 24h',
        ])->assertSessionHas('status');

        Mail::assertSentTimes(LowBalanceAlertMail::class, 1);
    }

    public function test_csv_bulk_sms_rest_api_receipt_kyc_upload_and_maintenance_reminder(): void
    {
        Mail::fake();
        Storage::fake('local');

        $tier = PricingTier::create(['tier_name' => 'Basic Plan']);
        ServicePrice::create(['pricing_tier_id' => $tier->id, 'service_name' => 'SMS', 'price' => 4.00]);

        $client = Client::create([
            'name' => 'Enterprise SMS Co',
            'email' => 'enterprise@q4iltd.com',
            'password' => Hash::make('Password123!'),
            'token_balance' => 500.00,
            'monthly_maintenance_fee' => 159500.00,
            'maintenance_enabled' => true,
            'status' => 'active',
            'pricing_tier_id' => $tier->id,
        ]);

        $other = Client::create([
            'name' => 'Other Co',
            'email' => 'other@q4iltd.com',
            'password' => Hash::make('Password123!'),
            'token_balance' => 100.00,
            'maintenance_enabled' => false,
            'status' => 'active',
        ]);

        ApiKey::create([
            'client_id' => $client->id,
            'key_name' => 'REST Key',
            'api_key' => 'q4_live_key_999',
        ]);

        // 1. Multi-recipient + CSV bulk SMS dispatch
        $csvFile = UploadedFile::fake()->createWithContent('numbers.csv', "phone_number\n+2348011111111\n+2348022222222\n");
        $this->actingAs($client)->post('/services/sms', [
            'to' => '+2348033333333',
            'recipients_csv' => $csvFile,
            'message' => 'Q4 Bulk SMS test',
        ])->assertSessionHas('status');

        $client->refresh();
        // 3 recipients * ₦4.00 = ₦12.00 deducted
        $this->assertEquals(488.00, $client->token_balance);

        // 2. REST API v1 SMS & Airtime with X-API-Key
        $this->postJson('/api/v1/sms/send', [
            'to' => '+2348044444444',
            'message' => 'OTP 1234',
        ], ['X-API-Key' => 'q4_live_key_999'])
            ->assertOk()
            ->assertJson(['ok' => true, 'sent' => 1]);

        $this->postJson('/api/v1/airtime/vend', [
            'phone_number' => '+2348044444444',
            'amount' => 100,
            'network' => 'MTN',
        ], ['X-API-Key' => 'q4_live_key_999'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $client->refresh();
        $this->assertEquals(384.00, $client->token_balance);

        // 3. Transaction Receipt view + IDOR prevention
        $tx = Transaction::create([
            'client_id' => $client->id,
            'type' => 'debit',
            'reference' => 'MAINT-202611-TEST',
            'amount' => 159500.00,
            'tokens_purchased' => 0,
            'status' => 'success',
            'gateway' => 'maintenance_fee',
            'notes' => 'Monthly Maintenance',
        ]);
        $this->actingAs($client)->get("/billing/transactions/{$tx->id}/receipt")
            ->assertOk()
            ->assertSee('MAINT-202611-TEST')
            ->assertSee('159,500.00');
        $this->actingAs($other)->get("/billing/transactions/{$tx->id}/receipt")
            ->assertStatus(403);

        // 4. KYC Upload
        $kycPdf = UploadedFile::fake()->create('cac-cert.pdf', 120, 'application/pdf');
        $this->actingAs($client)->post('/settings/profile/kyc', [
            'kyc_document' => $kycPdf,
            'kyc_notes' => 'RC 123456',
        ])->assertSessionHas('status');

        $client->refresh();
        $this->assertNotNull($client->kyc_document_path);
        $this->assertSame('pending', $client->kyc_status);
        Storage::disk('local')->assertExists($client->kyc_document_path);

        // 5. 20th-of-month Maintenance Reminder command runs once per month
        $this->artisan('q4:remind-maintenance')->assertExitCode(0);
        Mail::assertSentTimes(MaintenanceReminderMail::class, 1);

        $this->artisan('q4:remind-maintenance')->assertExitCode(0);
        Mail::assertSentTimes(MaintenanceReminderMail::class, 1);
    }
}
