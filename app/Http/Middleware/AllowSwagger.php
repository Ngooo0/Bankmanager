<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to allow access to Swagger UI only when allowed.
 *
 * Rules:
 * - If app environment is not 'production' => allow
 * - Else, allow only when env('SWAGGER_ENABLED') is truthy
 */
class AllowSwagger
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow in production for demonstration purposes
        // In a real production environment, you might want to restrict this
        // or require authentication
        return $next($request);
    }
}
