<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour gérer la limitation de débit et enregistrer les utilisateurs
 * qui atteignent la limite de taux
 */
class RatingLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $key = 'api:' . ($user ? $user->id : $request->ip());

        // Vérifier si l'utilisateur atteint la limite
        $limiter = RateLimiter::limiter('api');

        if ($limiter($key)) {
            // L'utilisateur atteint la limite, enregistrer l'incident
            $this->logRateLimitReached($request, $user);

            return response()->json([
                'success' => false,
                'message' => 'Trop de requêtes. Veuillez réessayer plus tard.',
                'error' => 'RATE_LIMIT_EXCEEDED'
            ], 429);
        }

        return $next($request);
    }

    /**
     * Enregistre quand un utilisateur atteint la limite de taux
     *
     * @param Request $request
     * @param mixed $user
     */
    protected function logRateLimitReached(Request $request, $user = null): void
    {
        $logData = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString(),
        ];

        if ($user) {
            $logData['user_id'] = $user->id;
            $logData['user_email'] = $user->email;
        }

        Log::warning('Rate limit atteint pour l\'utilisateur', $logData);

        // Ici, vous pourriez également enregistrer dans une table dédiée
        // pour un suivi plus détaillé des violations de limite
    }
}