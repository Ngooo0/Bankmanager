<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SwaggerController;

Route::get('/', function () {
    return view('welcome');
});

// Routes pour la documentation Swagger
Route::get('/api/documentation', [SwaggerController::class, 'redirect']);
Route::get('/docs', [SwaggerController::class, 'index']);

