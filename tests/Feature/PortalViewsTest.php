<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\PricingTier;
use App\Models\Role;
use App\Models\ServicePrice;
use App\Models\UssdMenu;
use App\Models\UssdMenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortalViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_auth_client_and_admin_pages_render_successfully(): void
    {
        Role::create(['role_name' => 'Admin']);
        $tier = PricingTier::create(['tier_name' => 'Standard']);
        ServicePrice::create(['pricing_tier_id' => $tier->id, 'service_name' => 'SMS', 'price' => 2.50]);

        $admin = Client::create([
            'name' => 'Admin User',
            'email' => 'admin@q4iltd.com',
            'password' => Hash::make('Password123!'),
            'is_admin' => true,
            'status' => 'active',
            'token_balance' => 5000,
            'ussd_code' => '*347*102#',
            'pricing_tier_id' => $tier->id,
        ]);

        $menu = UssdMenu::create([
            'client_id' => $admin->id,
            'menu_name' => 'Smoke Menu',
            'is_active' => true,
            'is_archived' => false,
        ]);

        UssdMenuItem::create([
            'menu_id' => $menu->id,
            'parent_item_id' => null,
            'option_trigger' => 'ROOT',
            'response_type' => 'CON',
            'response_text' => "Welcome\n1. Test",
        ]);

        // Guest routes
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/forgot-password')->assertOk();
        $this->get('/reset-password/testtoken')->assertOk();

        // Client portal routes
        $this->actingAs($admin)->get('/dashboard')->assertOk();
        $this->actingAs($admin)->get('/services')->assertOk();
        $this->actingAs($admin)->get('/ussd-menus')->assertOk();
        $this->actingAs($admin)->get("/ussd-menus/{$menu->id}")->assertOk();
        $this->actingAs($admin)->get('/billing')->assertOk();
        $this->actingAs($admin)->get('/api-keys')->assertOk();
        $this->actingAs($admin)->get('/team')->assertOk();
        $this->actingAs($admin)->get('/settings/profile')->assertOk();
        $this->actingAs($admin)->get('/settings/security')->assertOk();

        // Admin portal routes
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
        $this->actingAs($admin)->get('/admin/clients')->assertOk();
        $this->actingAs($admin)->get('/admin/pricing-tiers')->assertOk();
        $this->actingAs($admin)->get('/admin/logs')->assertOk();
        $this->actingAs($admin)->get('/admin/ussd-menus')->assertOk();
        $this->actingAs($admin)->get('/admin/announcements')->assertOk();
    }
}
