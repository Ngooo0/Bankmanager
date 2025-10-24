<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception pour les erreurs de stockage cloud
 */
class CloudStorageException extends Exception
{
    protected $errors;

    public function __construct(string $message = 'Erreur de stockage cloud', array $errors = [], int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => $this->errors,
        ], 500);
    }
}