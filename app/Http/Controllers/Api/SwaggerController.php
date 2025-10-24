<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * @OA\Tag(
 *     name="Documentation",
 *     description="Endpoints pour accéder à la documentation API"
 * )
 */
class SwaggerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/documentation",
     *     tags={"Documentation"},
     *     summary="Redirection vers la documentation Swagger",
     *     description="Redirige vers l'interface Swagger UI",
     *     @OA\Response(
     *         response=302,
     *         description="Redirection vers /docs"
     *     )
     * )
     */
    public function redirect()
    {
        try {
            // Test basique sans DB
            return response()->json([
                'status' => 'OK',
                'message' => 'Documentation Swagger accessible',
                'environment' => app()->environment(),
                'url' => config('app.url'),
                'documentation_url' => config('app.url') . '/docs',
                'swagger_json_url' => config('app.url') . '/docs/api-docs.json',
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'ERROR',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/docs",
     *     tags={"Documentation"},
     *     summary="Interface Swagger UI",
     *     description="Affiche l'interface interactive de documentation Swagger",
     *     @OA\Response(
     *         response=200,
     *         description="Interface Swagger UI affichée"
     *     )
     * )
     */
    public function index()
    {
        return view('vendor.l5-swagger.index');
    }
}