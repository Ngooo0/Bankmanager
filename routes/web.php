<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Routes pour la documentation Swagger
Route::get('/api/documentation', [App\Http\Controllers\Api\SwaggerController::class, 'redirect']);
Route::get('/docs', [App\Http\Controllers\Api\SwaggerController::class, 'index']);
