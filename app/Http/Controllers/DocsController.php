<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class DocsController extends Controller
{
    public function index(): View
    {
        return view('docs.swagger');
    }

    public function openapi(): Response
    {
        $path = resource_path('docs/openapi.yaml');

        abort_unless(file_exists($path), 404, 'OpenAPI spec not found.');

        return response(file_get_contents($path), 200, [
            'Content-Type' => 'application/yaml; charset=UTF-8',
        ]);
    }
}
