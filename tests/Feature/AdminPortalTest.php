<?php

namespace Tests\Feature;

use App\Mail\AnnouncementMail;
use App\Mail\GatewayAlertMail;
use App\Models\Client;
use App\Models\PricingTier;
use App\Models\Transaction;
use App\Models\UssdLog;
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

    public function test_admin_live_logs_and_at_csv_reconciliation(): void
    {
        $admin = Client::create([
            'name' => 'Super Admin',
            'email' => 'admin-rec@example.com',
            'password' => Hash::make('Password123!'),
            'is_admin' => true,
            'status' => 'active',
        ]);

        $tier = PricingTier::create(['tier_name' => 'Basic Plan']);
        $client = Client::create([
            'name' => 'Modern Lottery',
            'email' => 'ml-rec@example.com',
            'password' => Hash::make('Password123!'),
            'ussd_code' => '*347*102#',
            'token_balance' => 500.00,
            'status' => 'active',
            'pricing_tier_id' => $tier->id,
        ]);

        // Existing unreconciled 0.00 session
        $unreconciled = UssdLog::create([
            'client_id' => $client->id,
            'phone_number' => '+2348034202984',
            'session_id' => 'AT-UNREC-1',
            'service_code' => '*347*102#',
            'session_data' => '1*2',
            'cost' => 0,
            'client_price' => 0,
            'upstream_cost' => 0,
            'hops' => 4,
            'status' => 'In Progress',
            'created_at' => now(),
            'logged_at' => now(),
        ]);

        // Live AJAX endpoint returns JSON payload without full page reload
        $this->actingAs($admin)->getJson('/admin/logs/live?tab=ussd')
            ->assertOk()
            ->assertJsonStructure(['html', 'summary', 'counts', 'updated_at']);

        // Single-session manual reconcile
        $this->actingAs($admin)->post("/admin/logs/{$unreconciled->id}/reconcile", [
            'at_cost' => 7.50,
            'status' => 'Success',
            'duration_s' => 18,
        ])->assertSessionHas('status');

        $client->refresh();
        $this->assertEquals(470.00, $client->token_balance); // 7.50 * 4 = 30.00

        // CSV preview & apply reconciliation against AT export (supports both quoted and unquoted AT dates)
        $csv = implode("\n", [
            'Date,Service Code,Phone Number,Hops,Duration,Cost,Status',
            '"November 27, 2026 11:02 AM",*347*102#,+2348034202984,4,21s,NGN 15.0000,Success',
            'November 27, 2026 9:56 AM,*347*102#,+2349039955683,6,44s,NGN 22.5000,Success',
        ]);

        $previewRes = $this->actingAs($admin)->post('/admin/logs/reconcile', [
            'csv_text' => $csv,
            'action' => 'preview',
        ]);
        $previewRes->assertSessionHas('at_reconcile_preview');
        $preview = session('at_reconcile_preview');
        $this->assertSame(1, $preview['totals']['mismatch']);
        $this->assertSame(1, $preview['totals']['missing_local']);
        $this->assertSame(0, $preview['totals']['unknown_client']);

        $this->actingAs($admin)->post('/admin/logs/reconcile', [
            'csv_text' => $csv,
            'action' => 'apply',
        ])->assertSessionHas('status');

        // Row 1 delta: 60.00 - 30.00 = 30.00; Row 2 missing_local: 22.50 * 4 = 90.00 -> total 120.00 deducted
        $client->refresh();
        $this->assertEquals(350.00, $client->token_balance);
        $this->assertSame(2, UssdLog::query()->where('client_id', $client->id)->count());

        // Re-applying identical CSV stays idempotent
        $this->actingAs($admin)->post('/admin/logs/reconcile', [
            'csv_text' => $csv,
            'action' => 'apply',
        ])->assertSessionHas('status');
        $client->refresh();
        $this->assertEquals(350.00, $client->token_balance);
    }

    public function test_admin_can_delete_test_clients_and_fetch_perfex_invoices(): void
    {
        $admin = Client::create([
            'name' => 'Super Admin',
            'email' => 'admin-del@example.com',
            'password' => Hash::make('Password123!'),
            'is_admin' => true,
            'status' => 'active',
        ]);

        $modernLottery = Client::create([
            'name' => 'Modern Lottery',
            'email' => 'ml-keep@example.com',
            'password' => Hash::make('Password123!'),
            'ussd_code' => '*347*102#',
            'status' => 'active',
            'perfex_client_id' => 12,
        ]);

        $testA = Client::create([
            'name' => 'Test Staff A',
            'email' => 'test-a@example.com',
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);

        $testB = Client::create([
            'name' => 'Test Client B',
            'email' => 'test-b@example.com',
            'password' => Hash::make('Password123!'),
            'status' => 'pending',
        ]);

        $testC = Client::create([
            'name' => 'Test Client C',
            'email' => 'test-c@example.com',
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);

        Transaction::create([
            'client_id' => $testA->id,
            'type' => 'credit',
            'reference' => 'TEST-TX-A',
            'amount' => 100,
            'tokens_purchased' => 100,
            'status' => 'success',
            'gateway' => 'admin_manual',
        ]);

        // Cannot delete admin
        $this->actingAs($admin)->delete("/admin/clients/{$admin->id}")
            ->assertSessionHasErrors('client');
        $this->assertDatabaseHas('clients', ['id' => $admin->id]);

        // Single delete removes test client and its transactions
        $this->actingAs($admin)->delete("/admin/clients/{$testA->id}")
            ->assertSessionHas('status');
        $this->assertDatabaseMissing('clients', ['id' => $testA->id]);
        $this->assertDatabaseMissing('transactions', ['reference' => 'TEST-TX-A']);

        // Bulk delete removes testB & testC while preserving admin (even if submitted) and Modern Lottery
        $this->actingAs($admin)->post('/admin/clients/bulk-delete', [
            'client_ids' => [$admin->id, $testB->id, $testC->id],
        ])->assertSessionHas('status');

        $this->assertDatabaseMissing('clients', ['id' => $testB->id]);
        $this->assertDatabaseMissing('clients', ['id' => $testC->id]);
        $this->assertDatabaseHas('clients', ['id' => $admin->id]);
        $this->assertDatabaseHas('clients', ['id' => $modernLottery->id]);

        // Perfex CRM invoice & customer listing endpoint
        config([
            'services.perfex.url' => 'https://crm.q4iltd.com',
            'services.perfex.api_token' => 'perfex_token_123',
        ]);
        Http::fake([
            'crm.q4iltd.com/api/invoices' => Http::response([
                [
                    'id' => 55,
                    'prefix' => 'INV-',
                    'number' => '000055',
                    'clientid' => 12,
                    'company' => 'Modern Lottery',
                    'date' => '2026-11-27',
                    'duedate' => '2026-12-04',
                    'total' => '159500.00',
                    'status' => '1',
                ],
            ], 200),
            'crm.q4iltd.com/api/customers' => Http::response([
                ['userid' => 12, 'company' => 'Modern Lottery'],
            ], 200),
        ]);

        $this->actingAs($admin)->getJson('/admin/perfex/invoices')
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'invoices_ok' => true,
                'customers_ok' => true,
            ])
            ->assertJsonPath('invoices.0.number', 'INV-000055')
            ->assertJsonPath('invoices.0.status', 'Unpaid')
            ->assertJsonPath('customers.0.userid', 12);
    }

    public function test_admin_can_view_ussd_log_details_and_perfex_falls_back_to_q4i_api_bridge(): void
    {
        $admin = Client::create([
            'name' => 'Admin Q4',
            'email' => 'admin-q4-bridge@example.com',
            'password' => Hash::make('Password123!'),
            'is_admin' => true,
            'status' => 'active',
        ]);

        $client = Client::create([
            'name' => 'Modern Lottery Ltd',
            'email' => 'lottery-bridge@example.com',
            'password' => Hash::make('Password123!'),
            'ussd_code' => '*347*888#',
            'token_balance' => 1000,
            'monthly_maintenance_fee' => 159500,
            'status' => 'active',
        ]);

        $log = \App\Models\UssdLog::create([
            'client_id' => $client->id,
            'phone_number' => '+2348099998888',
            'session_id' => 'ATUid_admin_detail_1',
            'service_code' => '*347*888#',
            'network_name' => 'MTN Nigeria',
            'hops' => 2,
            'duration_ms' => 14000,
            'cost' => 30.00,
            'client_price' => 30.00,
            'upstream_cost' => 7.50,
            'status' => 'Success',
            'hops_trace' => [
                [
                    'hop' => 1,
                    'display_time' => '2026-11-28 02:00:00',
                    'input' => 'Dialed *347*888#',
                    'app_response' => 'CON Main Menu',
                    'error_message' => null,
                    'status' => 'In Progress',
                ],
                [
                    'hop' => 2,
                    'display_time' => '2026-11-28 02:00:14',
                    'input' => '1',
                    'app_response' => 'END Done',
                    'error_message' => null,
                    'status' => 'Success',
                ],
            ],
            'created_at' => now(),
            'reconciled_at' => now(),
        ]);

        $this->actingAs($admin)->getJson("/admin/logs/{$log->id}/details")
            ->assertOk()
            ->assertJson([
                'id' => $log->id,
                'is_admin' => true,
                'client_name' => 'Modern Lottery Ltd',
                'session_id' => 'ATUid_admin_detail_1',
                'billed' => '₦30.00',
                'upstream' => '₦7.50',
                'margin' => '₦22.50',
                'reconciled' => true,
            ])
            ->assertJsonCount(2, 'hops_trace');

        // Perfex CRM without PERFEX_API_PREFIX set falls back on 404 from /api/* to /q4i_api/api/*
        config([
            'services.perfex.url' => 'https://crm.q4iltd.com',
            'services.perfex.api_token' => 'q4i_bridge_token',
            'services.perfex.api_prefix' => '',
        ]);

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();

            if (str_contains($url, 'crm.q4iltd.com/api/')) {
                return Http::response('404 Page Not Found', 404);
            }

            if (str_contains($url, 'crm.q4iltd.com/q4i_api/api/contacts_search/')) {
                return Http::response([
                    ['userid' => 42, 'company' => 'Modern Lottery Ltd', 'email' => 'lottery-bridge@example.com'],
                ], 200);
            }

            if (str_contains($url, 'crm.q4iltd.com/q4i_api/api/customers')) {
                return Http::response([
                    ['userid' => 42, 'company' => 'Modern Lottery Ltd', 'active' => '1'],
                ], 200);
            }

            if (str_contains($url, 'crm.q4iltd.com/q4i_api/api/invoices')) {
                if (strtoupper($request->method()) === 'POST') {
                    return Http::response([
                        'status' => true,
                        'id' => 901,
                        'message' => 'Invoice added successfully.',
                    ], 200);
                }

                return Http::response([
                    [
                        'id' => 901,
                        'prefix' => 'INV-',
                        'number' => '000901',
                        'clientid' => 42,
                        'company' => 'Modern Lottery Ltd',
                        'total' => '159500.00',
                        'status' => 1,
                        'date' => '2026-11-28',
                        'duedate' => '2026-12-05',
                        'hash' => 'q4ihash901',
                    ],
                ], 200);
            }

            return Http::response([], 404);
        });

        $this->actingAs($admin)->getJson('/admin/perfex/invoices')
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'invoices_ok' => true,
                'customers_ok' => true,
            ])
            ->assertJsonPath('invoices.0.number', 'INV-000901')
            ->assertJsonPath('customers.0.userid', 42);

        $this->actingAs($admin)->post("/admin/clients/{$client->id}/perfex-invoice")
            ->assertSessionHas('status');

        $this->assertSame(42, (int) $client->fresh()->perfex_client_id);

        Http::assertSent(fn (\Illuminate\Http\Client\Request $request) => str_contains($request->url(), 'crm.q4iltd.com/q4i_api/api/contacts_search/'));
        Http::assertSent(fn (\Illuminate\Http\Client\Request $request) => $request->method() === 'POST' && str_contains($request->url(), 'crm.q4iltd.com/q4i_api/api/invoices'));
    }
}
