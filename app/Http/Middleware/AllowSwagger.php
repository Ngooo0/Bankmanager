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
        // Allow in non-production by default
        if (!app()->environment('production')) {
            return $next($request);
        }

        // In production, allow only if SWAGGER_ENABLED is truthy
        $enabled = filter_var(env('SWAGGER_ENABLED', false), FILTER_VALIDATE_BOOLEAN);

        if ($enabled) {
            return $next($request);
        }

        // Otherwise return 403 Forbidden
        return response()->json([
            'message' => 'Documentation unavailable.',
        ], 403);
    }
}
