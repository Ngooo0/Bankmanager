<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CompteController;
use App\Http\Controllers\Api\ExampleController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->middleware(['cors', 'throttle:api'])->group(function () {
    Route::middleware(['auth:api'])->group(function () {
        Route::get('/comptes', [CompteController::class, 'index']);
        Route::get('/comptes/archives/epargne', [CompteController::class, 'archivedEpargne'])->middleware('rating.limit');

        // Routes d'exemple pour démonstration
        Route::get('/clients', [ExampleController::class, 'indexClients']);
        Route::post('/clients', [ExampleController::class, 'storeClient']);
    });
});