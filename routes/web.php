<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Route pour la documentation Swagger
Route::get('/api/documentation', function () {
    return redirect('/docs');
});
