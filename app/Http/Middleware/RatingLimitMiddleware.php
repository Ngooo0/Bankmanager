<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour limiter le taux de requêtes et enregistrer les utilisateurs dépassant la limite
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
        $ip = $request->ip();
        $key = 'rate_limit_' . ($user ? $user->id : $ip);

        // Limite : 100 requêtes par minute
        $maxAttempts = 100;
        $decayMinutes = 1;

        $attempts = Cache::get($key, 0);

        if ($attempts >= $maxAttempts) {
            // Enregistrer l'utilisateur dépassant la limite
            Log::warning('Rate limit exceeded', [
                'user_id' => $user ? $user->id : null,
                'ip' => $ip,
                'attempts' => $attempts,
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'timestamp' => now()->toISOString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Trop de requêtes. Veuillez réessayer plus tard.',
                'retry_after' => Cache::get($key . '_retry_after', 60),
            ], 429);
        }

        // Incrémenter le compteur
        Cache::put($key, $attempts + 1, now()->addMinutes($decayMinutes));

        // Stocker le temps d'attente pour la réponse d'erreur
        Cache::put($key . '_retry_after', $decayMinutes * 60, now()->addMinutes($decayMinutes));

        $response = $next($request);

        return $response;
    }
}