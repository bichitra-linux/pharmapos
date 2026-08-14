<?php

namespace Tests\Feature;

use App\Models\LandingPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        LandingPage::create([
            'slug' => 'default',
            'content' => [],
            'meta_title' => 'PharmaPOS',
            'published_at' => now(),
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
