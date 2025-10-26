<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Routes pour la documentation Swagger
Route::get('/api/documentation', function () {
    return redirect()->away('https://bankmanager-5.onrender.com/docs');
});
Route::get('/docs', function () {
    return redirect()->away('https://bankmanager-5.onrender.com/docs');
});
