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
        return redirect('/docs');
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