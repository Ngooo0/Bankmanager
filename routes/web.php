<?php

use Illuminate\Support\Facades\Route;
use L5Swagger\L5Swagger;

Route::get('/', function () {
    return view('welcome');
});

// Routes L5-Swagger (assets et API)
L5Swagger::routes();

// Routes pour la documentation Swagger
Route::get('/api/documentation', function () {
    return view('vendor.l5-swagger.index');
})->middleware(['web']);
//route pour avoir l'interface swagger UI
Route::get('/docs', function () {
    return view('vendor.l5-swagger.index');
})->middleware(['web']);


