<?php

use App\Http\Controllers\DocsController;
use Illuminate\Support\Facades\Route;

// Route::redirect (not a closure) so `php artisan route:cache` can serialize it.
Route::redirect('/', '/docs');

Route::get('/docs', [DocsController::class, 'index'])->name('docs.index');
Route::get('/docs/openapi.yaml', [DocsController::class, 'openapi'])->name('docs.openapi');

