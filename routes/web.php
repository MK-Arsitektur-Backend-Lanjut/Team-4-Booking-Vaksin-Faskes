<?php

use App\Http\Controllers\DocsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs', [DocsController::class, 'index'])->name('docs.index');
Route::get('/docs/openapi.yaml', [DocsController::class, 'openapi'])->name('docs.openapi');
