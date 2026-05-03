<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_root_route_loads_the_public_landing_page_for_guests(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }

    public function test_the_public_components_page_loads_successfully(): void
    {
        $response = $this->get('/components');

        $response->assertOk();
    }
}
