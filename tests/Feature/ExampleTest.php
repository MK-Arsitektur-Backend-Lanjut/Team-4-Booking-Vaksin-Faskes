<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The root path redirects to the API documentation.
     */
    public function test_the_root_path_redirects_to_the_docs(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/docs');

        $this->get('/docs')->assertOk();
    }
}
