<?php

namespace Tests\Feature;

use App\Mail\AnnouncementMail;
use App\Mail\GatewayAlertMail;
use App\Models\Client;
use App\Models\WebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $client = Client::create([
            'name' => 'Regular Client',
            'email' => 'user@example.com',
            'password' => Hash::make('Password123!'),
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->actingAs($client)->get('/admin/dashboard')->assertStatus(403);
    }

    public function test_admin_can_manage_clients_tiers_and_broadcasts(): void
    {
        Mail::fake();

        $admin = Client::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('Password123!'),
            'is_admin' => true,
            'status' => 'active',
        ]);

        $target = Client::create([
            'name' => 'Target Client',
            'email' => 'target@example.com',
            'password' => Hash::make('Password123!'),
            'token_balance' => 50,
            'status' => 'active',
        ]);

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();

        // Create pricing tier
        $this->actingAs($admin)->post('/admin/pricing-tiers', [
            'tier_name' => 'Enterprise',
            'sms_price' => 1.50,
            'ussd_price' => 2.50,
            'airtime_price' => 0.90,
        ])->assertSessionHas('status');

        // Credit client wallet
        $this->actingAs($admin)->post("/admin/clients/{$target->id}/transaction", [
            'type' => 'credit',
            'amount' => 1000,
        ])->assertSessionHas('status');

        $target->refresh();
        $this->assertEquals(1050, $target->token_balance);

        // Broadcast announcement
        $this->actingAs($admin)->post('/admin/announcements', [
            'subject' => 'Maintenance Notice',
            'message' => 'All systems upgraded.',
        ])->assertSessionHas('status');

        Mail::assertSent(AnnouncementMail::class);
    }

    public function test_admin_maintenance_billing_kyc_and_audit_trail(): void
    {
        $admin = Client::create([
            'name' => 'Super Admin',
            'email' => 'admin2@example.com',
            'password' => Hash::make('Password123!'),
            'is_admin' => true,
            'status' => 'active',
        ]);

        $client = Client::create([
            'name' => 'Funded Merchant',
            'email' => 'merchant@example.com',
            'password' => Hash::make('Password123!'),
            'token_balance' => 200000.00,
            'monthly_maintenance_fee' => 159500.00,
            'maintenance_enabled' => true,
            'kyc_status' => 'pending',
            'status' => 'active',
        ]);

        // Update KYC to approved + record audit trail
        $this->actingAs($admin)->put("/admin/clients/{$client->id}", [
            'status' => 'active',
            'kyc_status' => 'approved',
            'kyc_notes' => 'CAC verified',
            'maintenance_enabled_present' => '1',
            'maintenance_enabled' => '1',
            'monthly_maintenance_fee' => 159500.00,
        ])->assertSessionHas('status');

        $client->refresh();
        $this->assertSame('approved', $client->kyc_status);
        $this->assertDatabaseHas('admin_audit_logs', [
            'actor_id' => $admin->id,
            'target_client_id' => $client->id,
            'action' => 'client.updated',
        ]);

        // Bill monthly maintenance
        $this->actingAs($admin)->post('/admin/clients/bill-maintenance')
            ->assertSessionHas('status');

        $client->refresh();
        $this->assertEquals(40500.00, $client->token_balance);
        $this->assertNotNull($client->last_maintenance_billed_at);
        $this->assertDatabaseHas('transactions', [
            'client_id' => $client->id,
            'type' => 'debit',
            'gateway' => 'maintenance_fee',
            'amount' => 159500.00,
        ]);

        // Second run in same month skips already-billed client
        $this->actingAs($admin)->post('/admin/clients/bill-maintenance')
            ->assertSessionHas('status');
        $client->refresh();
        $this->assertEquals(40500.00, $client->token_balance);
    }

    public function test_admin_can_download_kyc_sync_perfex_invoice_replay_webhook_and_alert(): void
    {
        Mail::fake();
        Storage::fake('local');

        $admin = Client::create([
            'name' => 'Super Admin',
            'email' => 'admin3@example.com',
            'password' => Hash::make('Password123!'),
            'is_admin' => true,
            'status' => 'active',
        ]);

        $kycPath = 'kyc/99/cac-doc.pdf';
        Storage::disk('local')->put($kycPath, '%PDF-1.4 fake');

        $client = Client::create([
            'name' => 'KYC Client',
            'email' => 'kyc@example.com',
            'password' => Hash::make('Password123!'),
            'ussd_code' => '*384*88#',
            'token_balance' => 100.00,
            'monthly_maintenance_fee' => 159500.00,
            'kyc_status' => 'pending',
            'kyc_document_path' => $kycPath,
            'perfex_client_id' => 42,
            'status' => 'active',
        ]);

        // Download KYC document
        $this->actingAs($admin)->get("/admin/clients/{$client->id}/kyc")
            ->assertOk();

        // Sync Perfex CRM Invoice (mocked Perfex REST API)
        config([
            'services.perfex.url' => 'https://crm.q4iltd.com',
            'services.perfex.api_token' => 'perfex_token_123',
        ]);
        Http::fake([
            'crm.q4iltd.com/api/invoices' => Http::response(['status' => true, 'id' => 701], 200),
        ]);

        $this->actingAs($admin)->post("/admin/clients/{$client->id}/perfex-invoice")
            ->assertSessionHas('status');

        // Webhook Replay for ussd_gateway
        $wh = WebhookLog::create([
            'provider' => 'ussd_gateway',
            'endpoint' => '/ussd',
            'status_code' => 200,
            'latency_ms' => 12,
            'outcome' => 'rejected',
            'payload' => json_encode([
                'sessionId' => 'REPLAY-1',
                'serviceCode' => '*999#',
                'phoneNumber' => '+2348000000000',
            ]),
            'created_at' => now(),
        ]);

        $this->actingAs($admin)->post("/admin/webhooks/{$wh->id}/replay")
            ->assertSessionHas('status');

        $this->assertDatabaseHas('webhook_logs', [
            'provider' => 'ussd_gateway',
            'outcome' => 'replayed:rejected',
        ]);

        // Health anomaly alert triggers at threshold=1
        config(['services.alerts.admin_email' => 'ops@q4iltd.com']);
        $this->artisan('q4:health-check --threshold=1')->assertExitCode(0);
        Mail::assertSent(GatewayAlertMail::class);
    }
}
