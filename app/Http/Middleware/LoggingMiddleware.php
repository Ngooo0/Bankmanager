<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour logger les opérations de création
 */
class LoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Informations de base de la requête
        $logData = [
            'timestamp' => now()->toISOString(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'host' => $request->getHost(),
            'operation' => $this->getOperationName($request),
            'ressource' => $this->getResourceName($request),
        ];

        // Ajouter les informations utilisateur si authentifié
        if ($request->user()) {
            $logData['user_id'] = $request->user()->id;
            $logData['user_email'] = $request->user()->email;
        }

        // Ajouter les données de requête pour les opérations POST/PUT/PATCH
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $logData['request_data'] = $this->sanitizeRequestData($request->all());
        }

        $response = $next($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // en millisecondes

        // Ajouter les informations de réponse
        $logData['duration_ms'] = $duration;
        $logData['status_code'] = $response->getStatusCode();
        $logData['response_size'] = strlen($response->getContent());

        // Logger selon le type d'opération
        $this->logOperation($logData);

        return $response;
    }

    /**
     * Détermine le nom de l'opération
     */
    protected function getOperationName(Request $request): string
    {
        $method = $request->method();
        $path = $request->path();

        // Déterminer l'opération basée sur la méthode HTTP et le chemin
        if (str_contains($path, '/comptes') && $method === 'POST') {
            return 'CREATION_COMPTE';
        }

        if (str_contains($path, '/clients') && $method === 'POST') {
            return 'CREATION_CLIENT';
        }

        if (str_contains($path, '/transactions') && $method === 'POST') {
            return 'CREATION_TRANSACTION';
        }

        // Opération par défaut
        return strtoupper($method) . '_OPERATION';
    }

    /**
     * Détermine le nom de la ressource
     */
    protected function getResourceName(Request $request): string
    {
        $path = $request->path();

        if (str_contains($path, '/comptes')) {
            return 'COMPTE';
        }

        if (str_contains($path, '/clients')) {
            return 'CLIENT';
        }

        if (str_contains($path, '/transactions')) {
            return 'TRANSACTION';
        }

        return 'UNKNOWN';
    }

    /**
     * Nettoie les données sensibles de la requête
     */
    protected function sanitizeRequestData(array $data): array
    {
        $sensitiveFields = ['password', 'mot_de_passe', 'code', 'token', 'api_key'];

        $sanitized = $data;

        foreach ($sensitiveFields as $field) {
            if (isset($sanitized[$field])) {
                $sanitized[$field] = '[REDACTED]';
            }
        }

        // Nettoyer récursivement les tableaux imbriqués
        array_walk_recursive($sanitized, function (&$value, $key) use ($sensitiveFields) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $value = '[REDACTED]';
            }
        });

        return $sanitized;
    }

    /**
     * Log l'opération selon son type
     */
    protected function logOperation(array $logData): void
    {
        $operation = $logData['operation'];
        $statusCode = $logData['status_code'];

        // Utiliser différents niveaux de log selon le statut
        if ($statusCode >= 500) {
            $level = 'error';
            $message = "Erreur serveur lors de l'opération {$operation}";
        } elseif ($statusCode >= 400) {
            $level = 'warning';
            $message = "Erreur client lors de l'opération {$operation}";
        } elseif ($statusCode >= 200 && $statusCode < 300) {
            $level = 'info';
            $message = "Opération {$operation} réussie";
        } else {
            $level = 'info';
            $message = "Opération {$operation} avec statut {$statusCode}";
        }

        // Logger avec le canal approprié
        Log::channel('operations')->{$level}($message, $logData);
    }
}