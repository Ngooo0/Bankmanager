<?php

namespace App\Jobs;

use App\Models\Compte;
use App\Services\CloudStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job pour archiver les comptes bloqués dont la date de blocage est échue
 */
class ArchiveExpiredBlockedAccounts implements ShouldQueue
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
    public function handle(CloudStorageService $cloudStorageService): void
    {
        Log::info('Début de l\'archivage des comptes bloqués expirés');

        $now = now();

        // Récupérer tous les comptes bloqués dont la date de déblocage prévue est dépassée
        $expiredBlockedAccounts = Compte::where('statut', 'bloque')
            ->whereNotNull('metadata->dateDeblocagePrevue')
            ->whereRaw("metadata->>'dateDeblocagePrevue' < ?", [$now->toISOString()])
            ->with(['client', 'transactions'])
            ->get();

        if ($expiredBlockedAccounts->isEmpty()) {
            Log::info('Aucun compte bloqué expiré trouvé');
            return;
        }

        Log::info("Nombre de comptes bloqués expirés trouvés: {$expiredBlockedAccounts->count()}");

        $archivedCount = 0;
        $errors = [];

        foreach ($expiredBlockedAccounts as $compte) {
            try {
                DB::transaction(function () use ($compte, $cloudStorageService, &$archivedCount) {
                    // Préparer les données d'archivage
                    $archiveData = [
                        'id' => $compte->id,
                        'numeroCompte' => $compte->numero_compte,
                        'titulaire' => $compte->titulaire,
                        'type' => $compte->type,
                        'solde' => $compte->solde,
                        'devise' => $compte->devise,
                        'dateCreation' => $compte->date_creation?->toISOString(),
                        'statut' => 'archive',
                        'dateArchivage' => $now->toISOString(),
                        'motifArchivage' => 'Blocage expiré - archivage automatique',
                        'client' => [
                            'id' => $compte->client->id,
                            'nom_complet' => $compte->client->nom_complet,
                            'email' => $compte->client->email,
                            'telephone' => $compte->client->telephone,
                        ],
                        'transactions' => $compte->transactions->map(function ($transaction) {
                            return [
                                'id' => $transaction->id,
                                'type' => $transaction->type,
                                'montant' => $transaction->montant,
                                'devise' => $transaction->devise,
                                'description' => $transaction->description,
                                'date_transaction' => $transaction->date_transaction?->toISOString(),
                                'statut' => $transaction->statut,
                                'solde_apres' => $transaction->solde_apres,
                            ];
                        })->toArray(),
                        'metadata' => array_merge($compte->metadata ?? [], [
                            'dateArchivage' => $now->toISOString(),
                            'motifArchivage' => 'Blocage expiré - archivage automatique',
                            'version' => ($compte->metadata['version'] ?? 0) + 1,
                        ])
                    ];

                    // Archiver dans le cloud
                    $cloudStorageService->archiveAccount($archiveData);

                    // Supprimer définitivement le compte de la base locale
                    $compte->transactions()->delete(); // Supprimer les transactions liées
                    $compte->forceDelete(); // Suppression définitive

                    $archivedCount++;

                    Log::info("Compte {$compte->numero_compte} archivé avec succès", [
                        'compte_id' => $compte->id,
                        'client_id' => $compte->client_id,
                        'date_archivage' => $now->toISOString()
                    ]);
                });

            } catch (\Exception $e) {
                $errorMessage = "Erreur lors de l'archivage du compte {$compte->numero_compte}: {$e->getMessage()}";
                Log::error($errorMessage, [
                    'compte_id' => $compte->id,
                    'exception' => $e
                ]);
                $errors[] = $errorMessage;
            }
        }

        Log::info("Archivage terminé: {$archivedCount} comptes archivés", [
            'total_processed' => $expiredBlockedAccounts->count(),
            'successfully_archived' => $archivedCount,
            'errors_count' => count($errors),
            'errors' => $errors
        ]);

        // Notification si des erreurs sont survenues
        if (!empty($errors)) {
            Log::warning('Des erreurs sont survenues lors de l\'archivage', [
                'errors' => $errors
            ]);
        }
    }

    /**
     * Gérer l'échec du job
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Échec définitif du job d\'archivage des comptes bloqués expirés', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Ici vous pourriez envoyer une notification à l'administrateur
    }
}