<?php

namespace Numerar\Contable\Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Numerar\Contable\Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
