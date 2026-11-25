<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\Client;
use App\Models\PricingTier;
use App\Models\Role;
use App\Models\ServicePrice;
use App\Models\UssdMenu;
use App\Models\UssdMenuItem;
use App\Models\UssdPricing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['id' => 1, 'role_name' => 'Admin'],
            ['id' => 2, 'role_name' => 'Billing Manager'],
            ['id' => 3, 'role_name' => 'Developer'],
            ['id' => 4, 'role_name' => 'Viewer'],
        ] as $role) {
            Role::firstOrCreate(['role_name' => $role['role_name']]);
        }

        foreach ([
            ['network_code' => '62130', 'network_name' => 'MTN Nigeria', 'tokens_per_interval' => 15],
            ['network_code' => '62120', 'network_name' => 'Airtel Nigeria', 'tokens_per_interval' => 10],
            ['network_code' => '62150', 'network_name' => 'GLO Nigeria', 'tokens_per_interval' => 20],
            ['network_code' => '62160', 'network_name' => '9mobile', 'tokens_per_interval' => 9],
        ] as $telco) {
            UssdPricing::firstOrCreate(
                ['network_code' => $telco['network_code']],
                $telco
            );
        }

        $standardTier = PricingTier::firstOrCreate(
            ['tier_name' => 'Standard'],
            ['description' => 'Default Q4I Communications tier']
        );

        foreach ([
            ['service_name' => 'SMS', 'price' => 2.50],
            ['service_name' => 'USSD', 'price' => 3.50],
            ['service_name' => 'AIRTIME', 'price' => 1.00],
        ] as $price) {
            ServicePrice::firstOrCreate([
                'pricing_tier_id' => $standardTier->id,
                'service_name' => $price['service_name'],
            ], ['price' => $price['price']]);
        }

        $admin = Client::firstOrCreate(
            ['email' => 'admin@q4iltd.com'],
            [
                'name' => 'Q4I Admin',
                'password' => Hash::make('Password123!'),
                'is_admin' => true,
                'status' => 'active',
                'token_balance' => 10000,
                'pricing_tier_id' => $standardTier->id,
            ]
        );

        $client = Client::firstOrCreate(
            ['email' => 'demo@q4iltd.com'],
            [
                'name' => 'Demo Comms Ltd',
                'password' => Hash::make('Password123!'),
                'is_admin' => false,
                'status' => 'active',
                'ussd_code' => '*347*102#',
                'sender_id' => 'Q4IComms',
                'token_balance' => 5000,
                'pricing_tier_id' => $standardTier->id,
            ]
        );

        if ($client->apiKeys()->count() === 0) {
            ApiKey::create([
                'client_id' => $client->id,
                'key_name' => 'Default Key',
                'api_key' => 'q4i_'.Str::random(32),
            ]);
        }

        if ($client->ussdMenus()->count() === 0) {
            $menu = UssdMenu::create([
                'client_id' => $client->id,
                'menu_name' => 'Demo Main Menu',
                'is_active' => true,
                'is_archived' => false,
            ]);

            $root = UssdMenuItem::create([
                'menu_id' => $menu->id,
                'parent_item_id' => null,
                'option_trigger' => 'ROOT',
                'response_type' => 'CON',
                'response_text' => "Welcome to Q4I Demo\n1. Account Info\n2. Support",
            ]);

            UssdMenuItem::create([
                'menu_id' => $menu->id,
                'parent_item_id' => $root->id,
                'option_trigger' => '1',
                'response_type' => 'END',
                'response_text' => 'Your Q4I Demo account is active.',
            ]);

            UssdMenuItem::create([
                'menu_id' => $menu->id,
                'parent_item_id' => $root->id,
                'option_trigger' => '2',
                'response_type' => 'END',
                'response_text' => 'Contact support@q4iltd.com for assistance.',
            ]);
        }
    }
}
