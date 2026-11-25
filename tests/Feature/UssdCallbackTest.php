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
}
