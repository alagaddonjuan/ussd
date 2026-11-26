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
            'ussd_code' => '*384*23#',
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

        // Public Q4 landing page for visitors (exact telco rates, maintenance fee, no upstream vendor/multipliers exposed)
        $this->get('/')
            ->assertOk()
            ->assertSee('Q4 International')
            ->assertSee('Q4 Services')
            ->assertSee('*384*23#')
            ->assertSee('Basic Plan')
            ->assertSee('Plus Plan')
            ->assertSee('Premium Plan')
            ->assertSee('Max Plan')
            ->assertSee('₦159,500')
            ->assertSee('₦30.00')
            ->assertSee('₦19.56')
            ->assertSee('₦48.00')
            ->assertSee('₦18.00')
            ->assertSee('Social Betting')
            ->assertSee('lotto.q4iltd.com')
            ->assertSee('Zenith')
            ->assertSee('Rexpay')
            ->assertSee('facebook.com', false)
            ->assertSee('instagram.com/q4international', false)
            ->assertSee('linkedin.com/company/q4i-limited', false)
            ->assertSee('threads.com/@q4international', false)
            ->assertSee('tiktok.com/@q4international', false)
            ->assertDontSee("Africa's Talking")
            ->assertDontSee('Africa&apos;s Talking', false)
            ->assertDontSee('*347*102#')
            ->assertDontSee('Multiplier');

        // Guest routes
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/forgot-password')->assertOk();
        $this->get('/reset-password/testtoken')->assertOk();

        // Client portal routes
        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertDontSee("Africa's Talking")
            ->assertDontSee('USSD AT');
        $this->actingAs($admin)->get('/services')->assertOk();
        $this->actingAs($admin)->get('/ussd-menus')->assertOk();
        $this->actingAs($admin)->get("/ussd-menus/{$menu->id}")
            ->assertOk()
            ->assertSee('Quick Text-to-Menu Builder')
            ->assertDontSee("Africa's Talking");
        $this->actingAs($admin)->get('/billing')->assertOk();
        $this->actingAs($admin)->get('/api-keys')->assertOk();
        $this->actingAs($admin)->get('/team')->assertOk();
        $this->actingAs($admin)->get('/settings/profile')->assertOk();
        $this->actingAs($admin)->get('/settings/security')->assertOk();

        // Authenticated users can also view landing page with dashboard shortcut
        $this->actingAs($admin)->get('/')->assertOk()->assertSee('Go to Dashboard');

        // Admin portal routes
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
        $this->actingAs($admin)->get('/admin/clients')->assertOk();
        $this->actingAs($admin)->get('/admin/pricing-tiers')->assertOk();
        $this->actingAs($admin)->get('/admin/logs')->assertOk();
        $this->actingAs($admin)->get('/admin/logs?tab=webhooks')->assertOk();
        $this->actingAs($admin)->get('/admin/logs?tab=audit')->assertOk();
        $this->actingAs($admin)->get('/admin/ussd-menus')->assertOk();
        $this->actingAs($admin)->get('/admin/announcements')->assertOk();
    }
}
