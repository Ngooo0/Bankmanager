<?php

namespace App\Events;

use App\Models\Client;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement déclenché lorsqu'un nouveau client est créé
 */
class ClientCreated
{
    use Dispatchable, SerializesModels;

    public $client;
    public $generatedPassword;
    public $generatedCode;

    /**
     * Créer une nouvelle instance d'événement
     *
     * @param Client $client
     * @param string $generatedPassword
     * @param string $generatedCode
     */
    public function __construct(Client $client, string $generatedPassword, string $generatedCode)
    {
        $this->client = $client;
        $this->generatedPassword = $generatedPassword;
        $this->generatedCode = $generatedCode;
    }
}