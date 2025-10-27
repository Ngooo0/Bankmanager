<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CompteController;
use App\Http\Controllers\Api\ExampleController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\TransactionController;

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
        // Routes Clients
        Route::apiResource('clients', ClientController::class);

        // Routes Transactions
        Route::apiResource('transactions', TransactionController::class);

        // Routes Comptes
        Route::get('/comptes', [CompteController::class, 'index']);
        Route::post('/comptes', [CompteController::class, 'store'])->middleware('logging');
        Route::get('/comptes/{compteId}', [CompteController::class, 'show']);
        Route::patch('/comptes/{compteId}', [CompteController::class, 'update'])->middleware('logging');
        Route::get('/comptes/archives/epargne', [CompteController::class, 'archivedEpargne'])->middleware('rating.limit');
    });
});