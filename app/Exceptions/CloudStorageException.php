<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception personnalisée pour les erreurs de stockage cloud
 */
class CloudStorageException extends Exception
{
    /**
     * Constructeur de l'exception
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Rapport de l'exception
     *
     * @return void
     */
    public function report()
    {
        // Log l'erreur dans un canal spécifique si nécessaire
        \Log::channel('cloud-storage')->error('Cloud Storage Error', [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString()
        ]);
    }

    /**
     * Rendu de l'exception pour l'API
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Erreur de service cloud',
            'error' => $this->getMessage(),
            'code' => 'CLOUD_STORAGE_ERROR'
        ], 500);
    }
}