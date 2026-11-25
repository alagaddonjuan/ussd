<?php

namespace Tests\Feature;

use App\Mail\AnnouncementMail;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
}
