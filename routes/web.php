<?php

use Illuminate\Support\Facades\Route;
use L5Swagger\L5Swagger;

Route::get('/', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'BankManager API is running',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0'
    ]);
});

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
});

// Routes L5-Swagger (assets et API)
L5Swagger::routes();


