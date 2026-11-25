<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_renders_landing_page_and_health_endpoint_responds(): void
    {
        $this->get('/')->assertOk()->assertSee('Q4 International');
        $this->get('/login')->assertOk();
        $this->getJson('/api/health')->assertOk()->assertJson(['status' => 'OK']);
    }
}
