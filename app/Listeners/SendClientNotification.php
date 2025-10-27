<?php

namespace App\Listeners;

use App\Events\ClientCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/**
 * Listener pour envoyer les notifications après création d'un client
 */
class SendClientNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Le nombre de fois que le job peut être tenté
     */
    public $tries = 3;

    /**
     * Le nombre de secondes pendant lesquelles le job doit attendre avant d'être retenté
     */
    public $backoff = 60;

    /**
     * Créer le listener d'événement
     */
    public function __construct()
    {
        //
    }

    /**
     * Gérer l'événement
     *
     * @param ClientCreated $event
     * @return void
     */
    public function handle(ClientCreated $event): void
    {
        $client = $event->client;
        $password = $event->generatedPassword;
        $code = $event->generatedCode;

        try {
            // Envoyer l'email d'authentification
            $this->sendAuthenticationEmail($client, $password);

            // Envoyer le SMS avec le code
            $this->sendVerificationSms($client, $code);

            Log::info('Notifications envoyées avec succès pour le client', [
                'client_id' => $client->id,
                'email' => $client->email,
                'telephone' => $client->telephone
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi des notifications', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Relancer l'exception pour que le job soit retenté
            throw $e;
        }
    }

    /**
     * Envoyer l'email d'authentification
     *
     * @param mixed $client
     * @param string $password
     * @return void
     */
    protected function sendAuthenticationEmail($client, string $password): void
    {
        // Ici vous pouvez utiliser une classe Mailable personnalisée
        // Pour l'exemple, nous utilisons une notification simple

        $details = [
            'subject' => 'Bienvenue sur BankManager - Vos identifiants de connexion',
            'greeting' => 'Bienvenue ' . $client->nom_complet . ' !',
            'body' => 'Votre compte a été créé avec succès. Voici vos identifiants de connexion :',
            'login_info' => [
                'email' => $client->email,
                'mot_de_passe_temporaire' => $password
            ],
            'action_text' => 'Se connecter',
            'action_url' => config('app.url') . '/login',
            'note' => 'Veuillez changer votre mot de passe lors de votre première connexion.'
        ];

        // Simulation d'envoi d'email (à remplacer par une vraie implémentation)
        Log::info('Email d\'authentification simulé', [
            'to' => $client->email,
            'subject' => $details['subject'],
            'password' => $password
        ]);
    }

    /**
     * Envoyer le SMS de vérification
     *
     * @param mixed $client
     * @param string $code
     * @return void
     */
    protected function sendVerificationSms($client, string $code): void
    {
        $message = "Bienvenue sur BankManager ! Votre code de vérification est : {$code}. Ce code est requis pour votre première connexion.";

        // Simulation d'envoi SMS (à remplacer par une vraie implémentation avec un service SMS)
        Log::info('SMS de vérification simulé', [
            'to' => $client->telephone,
            'message' => $message,
            'code' => $code
        ]);
    }

    /**
     * Gérer l'échec du job
     *
     * @param ClientCreated $event
     * @param \Exception $exception
     * @return void
     */
    public function failed(ClientCreated $event, $exception): void
    {
        Log::error('Échec définitif de l\'envoi des notifications', [
            'client_id' => $event->client->id,
            'error' => $exception->getMessage()
        ]);

        // Ici vous pourriez envoyer une notification à l'administrateur
        // ou mettre à jour le statut du client pour indiquer que les notifications n'ont pas été envoyées
    }
}