<?php

use Illuminate\Support\Facades\Route;
use L5Swagger\L5Swagger;

// Route de debug absolue - sans middleware
Route::get('/debug', function () {
    return response()->json([
        'status' => 'debug_ok',
        'message' => 'Debug route working - Laravel is responding',
        'timestamp' => now()->toISOString(),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'environment' => app()->environment(),
        'routes_loaded' => count(Route::getRoutes())
    ]);
})->middleware([]);

// Route de fallback pour le root
Route::get('/', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'BankManager API is running',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
        'debug_url' => url('/debug')
    ]);
})->middleware([]);

// Route de test simple
Route::get('/test', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Test route working',
        'routes' => [
            'swagger_ui' => url('/api/documentation'),
            'swagger_json' => url('/api/docs')
        ]
    ]);
})->middleware([]);

// Routes L5-Swagger (assets et API)
L5Swagger::routes();


