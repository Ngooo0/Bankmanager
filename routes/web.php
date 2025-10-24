<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SwaggerController;

Route::get('/', function () {
    return view('welcome');
});

// Route de debug temporaire
Route::get('/debug', function () {
    try {
        // Test de connexion à la base de données
        \DB::connection()->getPdo();
        $dbStatus = 'Connexion DB OK';

        // Test des migrations
        $migrations = \DB::table('migrations')->count();
        $migrationStatus = "Migrations: {$migrations} exécutées";

        // Test des tables principales
        $tables = [
            'clients' => \DB::table('clients')->count(),
            'comptes' => \DB::table('comptes')->count(),
            'transactions' => \DB::table('transactions')->count(),
        ];

        return response()->json([
            'status' => 'OK',
            'database' => $dbStatus,
            'migrations' => $migrationStatus,
            'tables' => $tables,
            'environment' => app()->environment(),
            'url' => config('app.url'),
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

