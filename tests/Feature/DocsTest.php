<?php

namespace Tests\Feature;

use Tests\TestCase;

class DocsTest extends TestCase
{
    public function test_swagger_ui_page_is_accessible(): void
    {
        $response = $this->get('/docs');

        $response->assertStatus(200);
        $response->assertSee('swagger-ui', false);
    }

    public function test_openapi_yaml_is_accessible(): void
    {
        $response = $this->get('/docs/openapi.yaml');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/yaml; charset=UTF-8');
        $response->assertSee('openapi: 3.0.3', false);
    }
}
