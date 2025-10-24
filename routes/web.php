<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SwaggerController;

Route::get('/', function () {
    return view('welcome');
});

// Route de debug temporaire
Route::get('/debug', function () {
    try {
        // Test basique sans DB
        return response()->json([
            'status' => 'OK',
            'message' => 'Application fonctionne',
            'environment' => app()->environment(),
            'url' => config('app.url'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'ERROR',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
});

// Routes pour la documentation Swagger
Route::get('/api/documentation', [SwaggerController::class, 'redirect']);
Route::get('/docs', [SwaggerController::class, 'index']);

