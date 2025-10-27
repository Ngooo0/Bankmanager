<?php

// Debug script to test PHP and basic Laravel functionality
// This bypasses Laravel's routing system entirely

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Basic PHP info
    $debug = [
        'status' => 'php_ok',
        'message' => 'PHP is working correctly',
        'timestamp' => date('c'),
        'php_version' => PHP_VERSION,
        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
        'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'unknown'
    ];

    // Check if Laravel bootstrap exists
    $laravel_bootstrap = __DIR__ . '/../bootstrap/app.php';
    $debug['laravel_bootstrap_exists'] = file_exists($laravel_bootstrap);

    if ($debug['laravel_bootstrap_exists']) {
        $debug['laravel_bootstrap_path'] = realpath($laravel_bootstrap);

        // Try to include Laravel bootstrap
        try {
            require_once $laravel_bootstrap;
            $debug['laravel_bootstrap_loaded'] = true;

            // Try to get Laravel version
            if (function_exists('app')) {
                $debug['laravel_loaded'] = true;
                $debug['laravel_version'] = app()->version();
                $debug['environment'] = app()->environment();
                $debug['routes_count'] = count(\Illuminate\Support\Facades\Route::getRoutes());
            } else {
                $debug['laravel_loaded'] = false;
                $debug['error'] = 'Laravel app() function not available';
            }
        } catch (Exception $e) {
            $debug['laravel_bootstrap_loaded'] = false;
            $debug['bootstrap_error'] = $e->getMessage();
        }
    } else {
        $debug['error'] = 'Laravel bootstrap file not found';
    }

    // Check environment file
    $env_file = __DIR__ . '/../.env';
    $debug['env_file_exists'] = file_exists($env_file);

    // Check storage directories
    $storage_dirs = [
        'storage' => is_writable(__DIR__ . '/../storage'),
        'storage/logs' => is_writable(__DIR__ . '/../storage/logs'),
        'storage/framework' => is_writable(__DIR__ . '/../storage/framework'),
        'bootstrap/cache' => is_writable(__DIR__ . '/../bootstrap/cache')
    ];
    $debug['storage_permissions'] = $storage_dirs;

    // Check key directories exist
    $dirs = [
        'app' => is_dir(__DIR__ . '/../app'),
        'routes' => is_dir(__DIR__ . '/../routes'),
        'config' => is_dir(__DIR__ . '/../config'),
        'vendor' => is_dir(__DIR__ . '/../vendor')
    ];
    $debug['directories'] = $dirs;

    echo json_encode($debug, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'PHP debug script failed',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}