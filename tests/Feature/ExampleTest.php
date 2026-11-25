<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_guests_to_login_and_health_endpoint_responds(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk();
        $this->getJson('/api/health')->assertOk()->assertJson(['status' => 'OK']);
    }
}
