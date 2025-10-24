<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Trait pour standardiser les réponses API
 */
trait ApiResponseTrait
{
    /**
     * Réponse de succès
     */
    protected function successResponse($data = null, string $message = 'Opération réussie', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Réponse d'erreur
     */
    protected function errorResponse(string $message = 'Une erreur est survenue', int $status = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Réponse de succès avec pagination
     */
    protected function paginatedResponse($data, $pagination, $links, string $message = 'Données récupérées avec succès'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => $pagination,
            'links' => $links,
        ]);
    }
}