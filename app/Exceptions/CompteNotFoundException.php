<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception personnalisée pour les comptes non trouvés
 */
class CompteNotFoundException extends Exception
{
    protected $compteId;

    /**
     * Constructeur de l'exception
     *
     * @param string $compteId
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $compteId, string $message = "", int $code = 0, \Throwable $previous = null)
    {
        $this->compteId = $compteId;
        $message = $message ?: "Le compte avec l'ID {$compteId} n'existe pas";
        parent::__construct($message, $code, $previous);
    }

    /**
     * Rapport de l'exception
     *
     * @return void
     */
    public function report()
    {
        \Log::info('Tentative d\'accès à un compte inexistant', [
            'compte_id' => $this->compteId,
            'timestamp' => now()->toISOString()
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
            'error' => [
                'code' => 'COMPTE_NOT_FOUND',
                'message' => $this->getMessage(),
                'details' => [
                    'compteId' => $this->compteId
                ]
            ]
        ], 404);
    }

    /**
     * Obtenir l'ID du compte
     *
     * @return string
     */
    public function getCompteId(): string
    {
        return $this->compteId;
    }
}