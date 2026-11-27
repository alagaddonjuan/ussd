<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\PricingTier;
use App\Models\ServicePrice;
use App\Models\UssdLog;
use App\Models\UssdMenu;
use App\Models\UssdMenuItem;
use App\Models\UssdPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UssdCallbackTest extends TestCase
{
    use RefreshDatabase;

    private function createMenuScenario(): array
    {
        $tier = PricingTier::create(['tier_name' => 'Gold']);
        ServicePrice::create([
            'pricing_tier_id' => $tier->id,
            'service_name' => 'USSD',
            'price' => 4.00,
        ]);

        UssdPricing::create([
            'network_code' => '62130',
            'network_name' => 'MTN Nigeria',
            'tokens_per_interval' => 15,
        ]);

        $client = Client::create([
            'name' => 'Modern Lottery',
            'email' => 'ml@example.com',
            'password' => Hash::make('secret123'),
            'ussd_code' => '*347*102#',
            'token_balance' => 100.00,
            'status' => 'active',
            'pricing_tier_id' => $tier->id,
        ]);

        $menu = UssdMenu::create([
            'client_id' => $client->id,
            'menu_name' => 'ML Menu',
            'is_active' => true,
            'is_archived' => false,
        ]);

        $root = UssdMenuItem::create([
            'menu_id' => $menu->id,
            'parent_item_id' => null,
            'option_trigger' => 'ROOT',
            'response_type' => 'CON',
            'response_text' => "Welcome to ML\n1. Play Games\n2. Support",
        ]);

        $games = UssdMenuItem::create([
            'menu_id' => $menu->id,
            'parent_item_id' => $root->id,
            'option_trigger' => '1',
            'response_type' => 'CON',
            'response_text' => "Games|Choose Game:\n1. Keno",
        ]);

        UssdMenuItem::create([
            'menu_id' => $menu->id,
            'parent_item_id' => $games->id,
            'option_trigger' => '1',
            'response_type' => 'API',
            'response_text' => 'Keno API',
        ]);

        UssdMenuItem::create([
            'menu_id' => $menu->id,
            'parent_item_id' => $root->id,
            'option_trigger' => '2',
            'response_type' => 'END',
            'response_text' => 'Contact support@q4iltd.com',
        ]);

        return [$client, $menu];
    }

    public function test_root_menu_and_telco_billing(): void
    {
        [$client] = $this->createMenuScenario();

        $res = $this->post('/ussd', [
            'sessionId' => 'AT-1',
            'serviceCode' => '*347*102#',
            'phoneNumber' => '+2348011112222',
            'networkCode' => '62130',
            'text' => '',
        ]);

        $res->assertOk();
        $this->assertSame("CON Welcome to ML\n1. Play Games\n2. Support", $res->getContent());

        $client->refresh();
        $this->assertEquals(85.00, $client->token_balance);
        $this->assertDatabaseHas('ussd_logs', [
            'client_id' => $client->id,
            'session_id' => 'AT-1',
            'session_data' => 'Main Menu',
            'cost' => 15.00,
        ]);
    }

    public function test_pipe_stripping_back_navigation_and_tier_fallback(): void
    {
        [$client] = $this->createMenuScenario();

        // Select option 1 (pipe label "Games|" should be stripped and "0. Back" appended)
        $res1 = $this->post('/api/v1/ussd', [
            'sessionId' => 'AT-2',
            'serviceCode' => '*347*102#',
            'phoneNumber' => '+2348011112222',
            'text' => '1',
        ]);
        $res1->assertOk();
        $this->assertSame("CON Choose Game:\n1. Keno\n0. Back", $res1->getContent());

        // Press 0 to go back to ROOT, then 2 to hit END screen
        $res2 = $this->post('/ussd_callback', [
            'sessionId' => 'AT-2',
            'serviceCode' => '*347*102#',
            'phoneNumber' => '+2348011112222',
            'text' => '1*0*2',
        ]);
        $res2->assertOk();
        $this->assertSame('END Contact support@q4iltd.com', $res2->getContent());

        $client->refresh();
        $this->assertEquals(92.00, $client->token_balance); // 2 hits * 4.00 tier price
    }

    public function test_api_node_forwards_to_external_ussd_endpoint(): void
    {
        $this->createMenuScenario();

        Http::fake([
            'api.modernlotterynigeria.com/*' => Http::response("CON Select Ball:\n1. Ball 1", 200),
        ]);

        $res = $this->post('/ussd', [
            'sessionId' => 'AT-3',
            'serviceCode' => '*347*102#',
            'phoneNumber' => '+2348011112222',
            'text' => '1*1',
        ]);

        $res->assertOk();
        $this->assertSame("CON Select Ball:\n1. Ball 1", $res->getContent());
    }

    public function test_unrecognized_code_returns_end_error(): void
    {
        $res = $this->post('/ussd', [
            'sessionId' => 'AT-4',
            'serviceCode' => '*999#',
            'phoneNumber' => '+2348011112222',
            'text' => '',
        ]);

        $res->assertOk();
        $this->assertSame('END Error: USSD Code not recognized.', $res->getContent());
    }

    public function test_africas_talking_ussd_cost_is_multiplied_by_tier_factor_and_reconciled(): void
    {
        $basicTier = PricingTier::create(['tier_name' => 'Basic Plan']);
        $plusTier = PricingTier::create(['tier_name' => 'Plus Plan']);
        $premiumTier = PricingTier::create(['tier_name' => 'Premium Plan']);
        $maxTier = PricingTier::create(['tier_name' => 'Max Plan']);

        // Basic (4x): hop charges provisional default (3.50 * 4 = 14.00), then AT /events reconciles "NGN 3.00" * 4 = 12.00
        $basicClient = Client::create([
            'name' => 'Basic Client',
            'email' => 'basic@example.com',
            'password' => Hash::make('secret123'),
            'ussd_code' => '*347*201#',
            'token_balance' => 100.00,
            'status' => 'active',
            'pricing_tier_id' => $basicTier->id,
        ]);
        $menu = UssdMenu::create([
            'client_id' => $basicClient->id,
            'menu_name' => 'Basic Menu',
            'is_active' => true,
            'is_archived' => false,
        ]);
        UssdMenuItem::create([
            'menu_id' => $menu->id,
            'parent_item_id' => null,
            'option_trigger' => 'ROOT',
            'response_type' => 'END',
            'response_text' => 'Thank you',
        ]);

        $this->post('/ussd', [
            'sessionId' => 'AT-BASIC-1',
            'serviceCode' => '*347*201#',
            'phoneNumber' => '+2348011110001',
            'text' => '',
        ])->assertOk();

        $basicClient->refresh();
        $this->assertEquals(86.00, $basicClient->token_balance);

        $this->post('/api/v1/ussd/events', [
            'sessionId' => 'AT-BASIC-1',
            'serviceCode' => '*347*201#',
            'phoneNumber' => '+2348011110001',
            'networkCode' => '62130',
            'durationInMillis' => 22000,
            'hopsCount' => 4,
            'status' => 'Success',
            'cost' => 'NGN 3.00',
        ])->assertOk();

        $basicClient->refresh();
        $this->assertEquals(88.00, $basicClient->token_balance);
        $this->assertDatabaseHas('ussd_logs', [
            'client_id' => $basicClient->id,
            'session_id' => 'AT-BASIC-1',
            'cost' => 12.00,
            'client_price' => 12.00,
            'upstream_cost' => 3.0,
            'duration_ms' => 22000,
            'hops' => 4,
            'network_name' => 'MTN Nigeria',
        ]);
        $this->assertDatabaseHas('webhook_logs', [
            'provider' => 'ussd_gateway',
            'status_code' => 200,
        ]);

        // Duplicate webhook stays idempotent
        $this->post('/api/v1/ussd/events', [
            'sessionId' => 'AT-BASIC-1',
            'serviceCode' => '*347*201#',
            'status' => 'Success',
            'cost' => 'NGN 3.00',
        ])->assertOk();
        $basicClient->refresh();
        $this->assertEquals(88.00, $basicClient->token_balance);

        // Plus (3.5x), Premium (2.9x), Max (2.5x)
        $plusClient = Client::create([
            'name' => 'Plus Client',
            'email' => 'plus@example.com',
            'password' => Hash::make('secret123'),
            'ussd_code' => '*347*202#',
            'token_balance' => 100.00,
            'status' => 'active',
            'pricing_tier_id' => $plusTier->id,
        ]);
        $plusMenu = UssdMenu::create(['client_id' => $plusClient->id, 'menu_name' => 'Plus', 'is_active' => true, 'is_archived' => false]);
        UssdMenuItem::create(['menu_id' => $plusMenu->id, 'option_trigger' => 'ROOT', 'response_type' => 'END', 'response_text' => 'Hi']);
        $this->post('/ussd', [
            'sessionId' => 'AT-PLUS-1',
            'serviceCode' => '*347*202#',
            'phoneNumber' => '+2348011110002',
            'text' => '',
            'cost' => 'NGN 4.00',
        ])->assertOk();
        $plusClient->refresh();
        $this->assertEquals(86.00, $plusClient->token_balance); // 100 - (4 * 3.5 = 14)

        $premiumClient = Client::create([
            'name' => 'Premium Client',
            'email' => 'premium@example.com',
            'password' => Hash::make('secret123'),
            'ussd_code' => '*347*203#',
            'token_balance' => 100.00,
            'status' => 'active',
            'pricing_tier_id' => $premiumTier->id,
        ]);
        $this->post('/api/v1/ussd/events', [
            'sessionId' => 'AT-PREM-1',
            'serviceCode' => '*347*203#',
            'phoneNumber' => '+2348011110003',
            'status' => 'Success',
            'cost' => 'NGN 10.00',
        ])->assertOk();
        $premiumClient->refresh();
        $this->assertEquals(71.00, $premiumClient->token_balance); // 100 - (10 * 2.9 = 29)

        $maxClient = Client::create([
            'name' => 'Max Client',
            'email' => 'max@example.com',
            'password' => Hash::make('secret123'),
            'ussd_code' => '*347*204#',
            'token_balance' => 100.00,
            'status' => 'active',
            'pricing_tier_id' => $maxTier->id,
        ]);
        $this->post('/ussd/events', [
            'sessionId' => 'AT-MAX-1',
            'serviceCode' => '*347*204#',
            'phoneNumber' => '+2348011110004',
            'status' => 'Success',
            'cost' => 'NGN 4.00',
        ])->assertOk();
        $maxClient->refresh();
        $this->assertEquals(90.00, $maxClient->token_balance); // 100 - (4 * 2.5 = 10)
    }

    public function test_text_to_menu_builder_imports_nested_tree_and_drives_callbacks(): void
    {
        $client = Client::create([
            'name' => 'Q4 Text Builder',
            'email' => 'builder@q4iltd.com',
            'password' => Hash::make('secret123'),
            'ussd_code' => '*384*500#',
            'token_balance' => 100.00,
            'status' => 'active',
        ]);

        $menu = UssdMenu::create([
            'client_id' => $client->id,
            'menu_name' => 'Q4 Flow',
            'is_active' => true,
            'is_archived' => false,
        ]);

        $outline = implode("\n", [
            'Welcome to Q4 Services',
            '  1. Play Games',
            '    1. Keno',
            '      1. Select Balls [END: Balls submitted]',
            '  2. Support [END: Email support@q4iltd.com]',
        ]);

        $this->actingAs($client)->post("/ussd-menus/{$menu->id}/import-text", [
            'menu_text' => $outline,
            'mode' => 'replace',
            'default_leaf_type' => 'END',
        ])->assertSessionHas('status');

        $this->assertDatabaseHas('ussd_menu_items', [
            'menu_id' => $menu->id,
            'option_trigger' => 'ROOT',
            'response_type' => 'CON',
        ]);

        // Dial ROOT -> should list 1. Play Games & 2. Support
        $rRoot = $this->post('/ussd', [
            'sessionId' => 'SIM-TEXT-1',
            'serviceCode' => '*384*500#',
            'phoneNumber' => '+2348010000001',
            'text' => '',
        ])->assertOk();
        $this->assertSame("CON Welcome to Q4 Services\n1. Play Games\n2. Support", $rRoot->getContent());

        // Navigate 1*1*1 -> hits deeply nested leaf END item
        $rDeep = $this->post('/ussd', [
            'sessionId' => 'SIM-TEXT-1',
            'serviceCode' => '*384*500#',
            'phoneNumber' => '+2348010000001',
            'text' => '1*1*1',
        ])->assertOk();
        $this->assertSame('END Balls submitted', $rDeep->getContent());

        // Snapshot & Rollback test: save v1 or verify auto-snapshot works cleanly
        $this->actingAs($client)->post("/ussd-menus/{$menu->id}/versions", [
            'label' => 'Production Release 1',
        ])->assertSessionHas('status');

        $version = $menu->versions()->firstOrFail();

        // Replace menu with a simple screen (auto-creates v2 backup before replace)
        $this->actingAs($client)->post("/ussd-menus/{$menu->id}/import-text", [
            'menu_text' => "Temporary Screen [END: Down for maintenance]",
            'mode' => 'replace',
            'default_leaf_type' => 'END',
        ])->assertSessionHas('status');

        $rTemp = $this->post('/ussd', [
            'sessionId' => 'SIM-TEXT-TEMP',
            'serviceCode' => '*384*500#',
            'phoneNumber' => '+2348010000001',
            'text' => '',
        ])->assertOk();
        $this->assertSame('END Down for maintenance', $rTemp->getContent());

        // Restore back to v1 ("Production Release 1")
        $this->actingAs($client)->post("/ussd-menus/{$menu->id}/versions/{$version->id}/restore")
            ->assertSessionHas('status');

        $rRestored = $this->post('/ussd', [
            'sessionId' => 'SIM-TEXT-RESTORED',
            'serviceCode' => '*384*500#',
            'phoneNumber' => '+2348010000001',
            'text' => '',
        ])->assertOk();
        $this->assertSame("CON Welcome to Q4 Services\n1. Play Games\n2. Support", $rRestored->getContent());
    }

    public function test_multi_hop_session_consolidates_without_zero_cost_duplicates(): void
    {
        $tier = PricingTier::create(['tier_name' => 'Basic Plan']);
        $client = Client::create([
            'name' => 'Modern Lottery',
            'email' => 'ml-multi@example.com',
            'password' => Hash::make('secret123'),
            'ussd_code' => '*347*102#',
            'token_balance' => 200.00,
            'status' => 'active',
            'pricing_tier_id' => $tier->id,
        ]);

        $menu = UssdMenu::create([
            'client_id' => $client->id,
            'menu_name' => 'ML Flow',
            'is_active' => true,
            'is_archived' => false,
        ]);
        $root = UssdMenuItem::create([
            'menu_id' => $menu->id,
            'parent_item_id' => null,
            'option_trigger' => 'ROOT',
            'response_type' => 'CON',
            'response_text' => "Welcome\n1. Games",
        ]);
        $games = UssdMenuItem::create([
            'menu_id' => $menu->id,
            'parent_item_id' => $root->id,
            'option_trigger' => '1',
            'response_type' => 'CON',
            'response_text' => "Pick\n1. Play",
        ]);
        UssdMenuItem::create([
            'menu_id' => $menu->id,
            'parent_item_id' => $games->id,
            'option_trigger' => '1',
            'response_type' => 'END',
            'response_text' => 'Ticket Confirmed',
        ]);

        foreach (['', '1', '1*0', '1*1'] as $text) {
            $this->post('/ussd', [
                'sessionId' => 'AT-CONSOLIDATE-1',
                'serviceCode' => '*347*102#',
                'phoneNumber' => '+2348034202984',
                'networkCode' => '62130',
                'text' => $text,
            ])->assertOk();
        }

        // Only ONE row should exist for the session (no duplicate hop rows)
        $this->assertSame(1, UssdLog::query()->where('session_id', 'AT-CONSOLIDATE-1')->count());
        $midLog = UssdLog::query()->where('session_id', 'AT-CONSOLIDATE-1')->firstOrFail();
        $this->assertSame(4, $midLog->hops);

        // Now AT /ussd/events arrives with final cost NGN 15.0000 -> 4x = 60.00
        $this->post('/ussd/events', [
            'sessionId' => 'AT-CONSOLIDATE-1',
            'serviceCode' => '*347*102#',
            'phoneNumber' => '+2348034202984',
            'networkCode' => '62130',
            'durationInMillis' => 21000,
            'hopsCount' => 4,
            'status' => 'Success',
            'cost' => 'NGN 15.0000',
        ])->assertOk();

        $this->assertSame(1, UssdLog::query()->where('session_id', 'AT-CONSOLIDATE-1')->count());
        $finalLog = UssdLog::query()->where('session_id', 'AT-CONSOLIDATE-1')->firstOrFail();
        $this->assertEquals(60.00, $finalLog->cost);
        $this->assertEquals(60.00, $finalLog->client_price);
        $this->assertEquals(15.00, $finalLog->upstream_cost);
        $this->assertSame(21000, $finalLog->duration_ms);
        $this->assertNotNull($finalLog->reconciled_at);

        $client->refresh();
        $this->assertEquals(140.00, $client->token_balance);
    }

    public function test_events_records_errors_low_balance_and_unknown_clients(): void
    {
        $tier = PricingTier::create(['tier_name' => 'Basic Plan']);
        $client = Client::create([
            'name' => 'Low Wallet Co',
            'email' => 'lowwallet@example.com',
            'password' => Hash::make('secret123'),
            'ussd_code' => '*347*777#',
            'token_balance' => 5.00,
            'status' => 'active',
            'pricing_tier_id' => $tier->id,
        ]);

        // Direct event where AT billed NGN 7.50 (4x = 30.00) must charge full 30.00 even if balance < 30
        $this->post('/ussd/events', [
            'sessionId' => 'AT-DIRECT-LOW-1',
            'serviceCode' => '*347*777#',
            'phoneNumber' => '+2349131108156',
            'networkCode' => '62130',
            'durationInMillis' => 1000,
            'hopsCount' => 1,
            'status' => 'Incomplete',
            'cost' => 'NGN 7.5000',
            'errorMessage' => 'User cancelled session',
        ])->assertOk();

        $client->refresh();
        $this->assertEquals(-25.00, $client->token_balance);
        $this->assertDatabaseHas('ussd_logs', [
            'session_id' => 'AT-DIRECT-LOW-1',
            'status' => 'Incomplete',
            'gateway_error' => 'User cancelled session',
            'upstream_cost' => 7.5,
            'client_price' => 30.00,
        ]);

        // Subsequent Hop 1 with <= 0 balance is rejected while still logged
        $menu = UssdMenu::create(['client_id' => $client->id, 'menu_name' => 'M', 'is_active' => true]);
        UssdMenuItem::create(['menu_id' => $menu->id, 'option_trigger' => 'ROOT', 'response_type' => 'CON', 'response_text' => 'Hi']);

        $rejected = $this->post('/ussd', [
            'sessionId' => 'AT-ZERO-BAL-1',
            'serviceCode' => '*347*777#',
            'phoneNumber' => '+2349131108156',
            'text' => '',
        ])->assertOk();
        $this->assertStringContainsString('Insufficient wallet balance', $rejected->getContent());
        $this->assertDatabaseHas('ussd_logs', [
            'session_id' => 'AT-ZERO-BAL-1',
            'status' => 'Rejected',
        ]);

        // Unknown service code in /events records error outcome in webhook_logs
        $this->post('/ussd/events', [
            'sessionId' => 'AT-UNKNOWN-99',
            'serviceCode' => '*999*999#',
            'status' => 'Success',
            'cost' => 'NGN 7.5000',
        ])->assertOk();
        $this->assertDatabaseHas('webhook_logs', [
            'endpoint' => '/ussd/events',
            'outcome' => 'error:unknown_client',
        ]);
    }
}
