<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Routes pour la documentation Swagger
Route::get('/api/documentation', function () {
    return redirect('/docs');
});

Route::get('/docs', function () {
    return view('vendor.l5-swagger.index');
});

