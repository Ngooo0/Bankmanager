<?php

namespace App\Jobs;

use App\Models\Compte;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job pour désarchiver (débloquer) les comptes bloqués dont la date de fin de blocage est échue
 */
class UnblockExpiredAccounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Le nombre de fois que le job peut être tenté
     */
    public $tries = 3;

    /**
     * Le nombre de secondes pendant lesquelles le job doit attendre avant d'être retenté
     */
    public $backoff = 60;

    /**
     * Créer une nouvelle instance du job
     */
    public function __construct()
    {
        //
    }

    /**
     * Exécuter le job
     */
    public function handle(): void
    {
        Log::info('Début du déblocage automatique des comptes bloqués expirés');

        $now = now();

        // Récupérer tous les comptes bloqués dont la date de déblocage prévue est dépassée
        $expiredBlockedAccounts = Compte::where('statut', 'bloque')
            ->whereNotNull('metadata->dateDeblocagePrevue')
            ->whereRaw("metadata->>'dateDeblocagePrevue' < ?", [$now->toISOString()])
            ->with('client')
            ->get();

        if ($expiredBlockedAccounts->isEmpty()) {
            Log::info('Aucun compte bloqué expiré trouvé pour le déblocage');
            return;
        }

        Log::info("Nombre de comptes bloqués expirés à débloquer: {$expiredBlockedAccounts->count()}");

        $unblockedCount = 0;
        $errors = [];

        foreach ($expiredBlockedAccounts as $compte) {
            try {
                DB::transaction(function () use ($compte, $now, &$unblockedCount) {
                    // Mettre à jour le statut du compte à actif
                    $metadata = $compte->metadata ?? [];
                    $metadata['dateDeblocageAutomatique'] = $now->toISOString();
                    $metadata['motifDeblocageAutomatique'] = 'Déblocage automatique - période de blocage expirée';

                    $compte->update([
                        'statut' => 'actif',
                        'metadata' => $metadata
                    ]);

                    $unblockedCount++;

                    Log::info("Compte {$compte->numero_compte} débloqué automatiquement", [
                        'compte_id' => $compte->id,
                        'client_id' => $compte->client_id,
                        'date_deblocage' => $now->toISOString(),
                        'date_prevue' => $compte->metadata['dateDeblocagePrevue'] ?? null
                    ]);
                });

            } catch (\Exception $e) {
                $errorMessage = "Erreur lors du déblocage automatique du compte {$compte->numero_compte}: {$e->getMessage()}";
                Log::error($errorMessage, [
                    'compte_id' => $compte->id,
                    'exception' => $e
                ]);
                $errors[] = $errorMessage;
            }
        }

        Log::info("Déblocage automatique terminé: {$unblockedCount} comptes débloqués", [
            'total_processed' => $expiredBlockedAccounts->count(),
            'successfully_unblocked' => $unblockedCount,
            'errors_count' => count($errors),
            'errors' => $errors
        ]);

        // Notification si des erreurs sont survenues
        if (!empty($errors)) {
            Log::warning('Des erreurs sont survenues lors du déblocage automatique', [
                'errors' => $errors
            ]);
        }

        // Ici vous pourriez envoyer une notification aux clients dont les comptes ont été débloqués
        if ($unblockedCount > 0) {
            // TODO: Implémenter la notification aux clients
            Log::info("Notification à envoyer aux {$unblockedCount} clients dont les comptes ont été débloqués");
        }
    }

    /**
     * Gérer l'échec du job
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Échec définitif du job de déblocage automatique des comptes bloqués expirés', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Ici vous pourriez envoyer une notification à l'administrateur
    }
}