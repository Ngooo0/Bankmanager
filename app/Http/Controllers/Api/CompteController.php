<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Compte;
use App\Services\CloudStorageService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Comptes",
 *     description="API Endpoints pour la gestion des comptes bancaires"
 * )
 */
class CompteController extends Controller
{
    use ApiResponseTrait;

    protected $cloudStorageService;

    public function __construct(CloudStorageService $cloudStorageService)
    {
        $this->cloudStorageService = $cloudStorageService;
    }
    /**
     * @OA\Get(
     *     path="/api/v1/comptes",
     *     tags={"Comptes"},
     *     summary="Lister tous les comptes",
     *     description="Récupère la liste paginée de tous les comptes bancaires non supprimés (type épargne ou chèque, statut actif)",
     *     security={{"passport": {"read-comptes"}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"epargne", "cheque"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"actif", "bloque", "ferme"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche par titulaire ou numéro",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"dateCreation", "solde", "titulaire"}, default="dateCreation")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Ordre",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     * @OA\Response(
     *         response=200,
     *         description="Liste des comptes récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="numeroCompte", type="string", example="C00123456"),
     *                 @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
     *                 @OA\Property(property="type", type="string", example="epargne"),
     *                 @OA\Property(property="solde", type="number", format="decimal", example=1250000),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
     *                 @OA\Property(property="statut", type="string", example="bloque"),
     *                 @OA\Property(property="motifBlocage", type="string", example="Inactivité de 30+ jours", nullable=true),
     *                 @OA\Property(property="metadata", type="object",
     *                     @OA\Property(property="derniereModification", type="string", format="date-time"),
     *                     @OA\Property(property="version", type="integer")
     *                 )
     *             )),
     *             @OA\Property(property="pagination", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        $query = Compte::with('client')->nonSupprimes()
            ->whereIn('type', ['epargne', 'cheque'])
            ->where('statut', 'actif');

        // Filtrage par rôle : admin voit tous, client voit ses comptes
        if ($user->role !== 'admin') {
            $query->where('client_id', $user->client_id ?? $user->id);
        }

        // Filtrage par type
        if ($request->has('type') && in_array($request->type, ['epargne', 'cheque'])) {
            $query->where('type', $request->type);
        }

        // Filtrage par statut
        if ($request->has('statut') && in_array($request->statut, ['actif', 'bloque', 'ferme'])) {
            $query->where('statut', $request->statut);
        }

        // Recherche par titulaire ou numéro
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('titulaire', 'like', "%{$search}%")
                  ->orWhere('numero_compte', 'like', "%{$search}%");
            });
        }

        // Tri
        $sortField = $request->get('sort', 'dateCreation');
        $sortOrder = $request->get('order', 'desc');

        $allowedSorts = ['dateCreation' => 'date_creation', 'solde' => 'solde', 'titulaire' => 'titulaire'];
        if (array_key_exists($sortField, $allowedSorts)) {
            $query->orderBy($allowedSorts[$sortField], $sortOrder);
        } else {
            $query->orderBy('date_creation', 'desc');
        }

        $limit = min($request->get('limit', 10), 100);
        $comptes = $query->paginate($limit);

        // Formater les données selon la spécification
        $data = $comptes->getCollection()->map(function ($compte) {
            return [
                'id' => $compte->id,
                'numeroCompte' => $compte->numero_compte,
                'titulaire' => $compte->titulaire,
                'type' => $compte->type,
                'solde' => $compte->solde,
                'devise' => $compte->devise,
                'dateCreation' => $compte->date_creation?->toISOString(),
                'statut' => $compte->statut,
                'motifBlocage' => $compte->statut === 'bloque' ? ($compte->metadata['motifBlocage'] ?? null) : null,
                'metadata' => $compte->metadata,
            ];
        });

        return $this->paginatedResponse(
            $data,
            [
                'currentPage' => $comptes->currentPage(),
                'totalPages' => $comptes->lastPage(),
                'totalItems' => $comptes->total(),
                'itemsPerPage' => $comptes->perPage(),
                'hasNext' => $comptes->hasMorePages(),
                'hasPrevious' => $comptes->currentPage() > 1
            ],
            [
                'self' => $request->fullUrl(),
                'next' => $comptes->nextPageUrl(),
                'previous' => $comptes->previousPageUrl(),
                'first' => $request->fullUrlWithQuery(['page' => 1]),
                'last' => $request->fullUrlWithQuery(['page' => $comptes->lastPage()])
            ]
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/comptes",
     *     tags={"Comptes"},
     *     summary="Créer un nouveau compte",
     *     description="Crée un nouveau compte bancaire avec création automatique du client si nécessaire. Génère un mot de passe et un code de vérification, envoie un email et un SMS.",
     *     security={{"passport": {"write-comptes"}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "soldeInitial", "devise", "client"},
     *             @OA\Property(property="type", type="string", enum={"epargne", "cheque"}, description="Type de compte"),
     *             @OA\Property(property="soldeInitial", type="number", format="decimal", minimum=10000, description="Solde initial (minimum 10 000)"),
     *             @OA\Property(property="devise", type="string", maxLength=3, description="Devise (ex: FCFA, USD)"),
     *             @OA\Property(property="client", type="object", description="Informations du client",
     *                 @OA\Property(property="id", type="string", format="uuid", description="ID du client existant (optionnel)"),
     *                 @OA\Property(property="titulaire", type="string", minLength=2, maxLength=255, description="Nom complet du titulaire"),
     *                 @OA\Property(property="nci", type="string", description="Numéro NCI sénégalais (13 chiffres + lettre)"),
     *                 @OA\Property(property="email", type="string", format="email", description="Email unique"),
     *                 @OA\Property(property="telephone", type="string", description="Téléphone sénégalais (+221XXXXXXXXX)"),
     *                 @OA\Property(property="adresse", type="string", minLength=5, maxLength=500, description="Adresse")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte créé avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="660f9511-f30c-52e5-b827-557766551111"),
     *                 @OA\Property(property="numeroCompte", type="string", example="C00123460"),
     *                 @OA\Property(property="titulaire", type="string", example="Cheikh Sy"),
     *                 @OA\Property(property="type", type="string", example="cheque"),
     *                 @OA\Property(property="solde", type="number", format="decimal", example=500000),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2025-10-19T10:30:00Z"),
     *                 @OA\Property(property="statut", type="string", example="actif"),
     *                 @OA\Property(property="metadata", type="object",
     *                     @OA\Property(property="derniereModification", type="string", format="date-time"),
     *                     @OA\Property(property="version", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="VALIDATION_ERROR"),
     *                 @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
     *                 @OA\Property(property="details", type="object",
     *                     @OA\Property(property="soldeInitial", type="array", @OA\Items(type="string", example="Le solde initial doit être d'au moins 10 000 FCFA."))
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function store(StoreCompteRequest $request): JsonResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, &$compte, &$client) {
            // Vérifier si le client existe ou le créer
            if (isset($validated['client']['id'])) {
                // Client existant
                $client = Client::findOrFail($validated['client']['id']);
            } else {
                // Créer un nouveau client
                $generatedPassword = $this->generateSecurePassword();
                $generatedCode = $this->generateVerificationCode();

                // Parser le nom complet
                $nameParts = explode(' ', $validated['client']['titulaire'], 2);
                $prenom = $nameParts[0] ?? '';
                $nom = $nameParts[1] ?? $prenom; // Si pas d'espace, utiliser le prénom comme nom

                $client = Client::create([
                    'prenom' => $prenom,
                    'nom' => $nom,
                    'email' => $validated['client']['email'],
                    'telephone' => $validated['client']['telephone'],
                    'adresse' => $validated['client']['adresse'],
                    'nci' => $validated['client']['nci'],
                    'pays' => 'Sénégal',
                    'statut' => 'Actif',
                    'password' => $generatedPassword,
                    'code_verification' => $generatedCode,
                ]);
            }

            // Créer le compte
            $compte = Compte::create([
                'client_id' => $client->id,
                'titulaire' => $client->nom_complet,
                'type' => $validated['type'],
                'solde' => $validated['soldeInitial'],
                'devise' => $validated['devise'],
                'date_creation' => now(),
                'statut' => 'actif',
            ]);

            // Créer une transaction d'ouverture
            $compte->transactions()->create([
                'type' => 'depot',
                'montant' => $compte->solde,
                'devise' => $compte->devise,
                'description' => 'Ouverture du compte avec solde initial',
                'date_transaction' => now(),
                'statut' => 'validee',
                'solde_apres' => $compte->solde,
            ]);

            // Déclencher l'événement de création du client (uniquement pour les nouveaux clients)
            if (!isset($validated['client']['id'])) {
                \App\Events\ClientCreated::dispatch($client, $generatedPassword, $generatedCode);
            }
        });

        // Formater la réponse selon la spécification
        $data = [
            'id' => $compte->id,
            'numeroCompte' => $compte->numero_compte,
            'titulaire' => $compte->titulaire,
            'type' => $compte->type,
            'solde' => $compte->solde,
            'devise' => $compte->devise,
            'dateCreation' => $compte->date_creation?->toISOString(),
            'statut' => $compte->statut,
            'metadata' => $compte->metadata,
        ];

        return $this->successResponse($data, 'Compte créé avec succès', 201);
    }

    /**
     * Génère un mot de passe sécurisé
     */
    protected function generateSecurePassword(): string
    {
        return \Illuminate\Support\Str::random(12) . rand(100, 999);
    }

    /**
     * Génère un code de vérification à 6 chiffres
     */
    protected function generateVerificationCode(): string
    {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/comptes/{compteId}",
     *     tags={"Comptes"},
     *     summary="Afficher un compte",
     *     description="Récupère les détails d'un compte bancaire spécifique. Recherche d'abord en local (comptes chèque ou épargne actifs), puis dans le cloud si non trouvé.",
     *     security={{"passport": {"read-comptes"}}},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="UUID du compte",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="numeroCompte", type="string", example="C00123456"),
     *                 @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
     *                 @OA\Property(property="type", type="string", example="epargne"),
     *                 @OA\Property(property="solde", type="number", format="decimal", example=1250000),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
     *                 @OA\Property(property="statut", type="string", example="bloque"),
     *                 @OA\Property(property="motifBlocage", type="string", example="Inactivité de 30+ jours", nullable=true),
     *                 @OA\Property(property="metadata", type="object",
     *                     @OA\Property(property="derniereModification", type="string", format="date-time"),
     *                     @OA\Property(property="version", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="COMPTE_NOT_FOUND"),
     *                 @OA\Property(property="message", type="string", example="Le compte avec l'ID spécifié n'existe pas"),
     *                 @OA\Property(property="details", type="object",
     *                     @OA\Property(property="compteId", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function show(string $compteId): JsonResponse
    {
        $user = auth()->user();

        // Recherche en local d'abord (comptes chèque ou épargne actifs non supprimés)
        $compte = Compte::with(['client', 'transactions' => function ($query) {
            $query->latest()->limit(10);
        }])
        ->nonSupprimes()
        ->whereIn('type', ['epargne', 'cheque'])
        ->where('statut', 'actif')
        ->find($compteId);

        // Vérification des autorisations pour les comptes locaux
        if ($compte) {
            if ($user->role !== 'admin' && $compte->client_id !== $user->client_id) {
                return $this->errorResponse('Accès non autorisé à ce compte', 403);
            }

            // Formater les données selon la spécification
            $data = [
                'id' => $compte->id,
                'numeroCompte' => $compte->numero_compte,
                'titulaire' => $compte->titulaire,
                'type' => $compte->type,
                'solde' => $compte->solde,
                'devise' => $compte->devise,
                'dateCreation' => $compte->date_creation?->toISOString(),
                'statut' => $compte->statut,
                'motifBlocage' => $compte->statut === 'bloque' ? ($compte->metadata['motifBlocage'] ?? null) : null,
                'metadata' => $compte->metadata,
            ];

            return $this->successResponse($data);
        }

        // Si non trouvé en local, rechercher dans le cloud (comptes épargne archivés)
        try {
            $archivedComptes = $this->cloudStorageService->getArchivedEpargneComptes();

            $archivedCompte = collect($archivedComptes)->firstWhere('id', $compteId);

            if ($archivedCompte) {
                // Vérifier si l'utilisateur a accès (admin seulement pour les comptes archivés)
                if ($user->role !== 'admin') {
                    return $this->errorResponse('Accès non autorisé aux comptes archivés', 403);
                }

                return $this->successResponse($archivedCompte);
            }
        } catch (\Exception $e) {
            // Log l'erreur mais continue pour lancer l'exception CompteNotFound
            \Log::warning('Erreur lors de la recherche dans le cloud', [
                'compte_id' => $compteId,
                'error' => $e->getMessage()
            ]);
        }

        // Si non trouvé nulle part, lancer l'exception personnalisée
        throw new \App\Exceptions\CompteNotFoundException($compteId);
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/comptes/{compteId}",
     *     tags={"Comptes"},
     *     summary="Mettre à jour un compte",
     *     description="Met à jour les informations d'un compte bancaire et de son titulaire. Tous les champs sont optionnels mais au moins un champ doit être fourni.",
     *     security={{"passport": {"write-comptes"}}},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="UUID du compte",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="titulaire", type="string", minLength=2, maxLength=255, description="Nouveau nom du titulaire"),
     *             @OA\Property(property="informationsClient", type="object", description="Informations du client à mettre à jour",
     *                 @OA\Property(property="telephone", type="string", description="Nouveau numéro de téléphone sénégalais"),
     *                 @OA\Property(property="email", type="string", format="email", description="Nouvel email"),
     *                 @OA\Property(property="password", type="string", minLength=8, description="Nouveau mot de passe"),
     *                 @OA\Property(property="nci", type="string", description="Nouveau numéro NCI sénégalais")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte mis à jour avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="numeroCompte", type="string", example="C00123456"),
     *                 @OA\Property(property="titulaire", type="string", example="Amadou Diallo Junior"),
     *                 @OA\Property(property="type", type="string", example="epargne"),
     *                 @OA\Property(property="solde", type="number", format="decimal", example=1250000),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
     *                 @OA\Property(property="statut", type="string", example="bloque"),
     *                 @OA\Property(property="metadata", type="object",
     *                     @OA\Property(property="derniereModification", type="string", format="date-time"),
     *                     @OA\Property(property="version", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides ou aucun champ fourni",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="VALIDATION_ERROR"),
     *                 @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
     *                 @OA\Property(property="details", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function update(UpdateCompteRequest $request, string $compteId): JsonResponse
    {
        $user = auth()->user();
        $validated = $request->validated();

        // Récupérer le compte avec son client
        $compte = Compte::with('client')->findOrFail($compteId);

        // Vérification des autorisations
        if ($user->role !== 'admin' && $compte->client_id !== $user->client_id) {
            return $this->errorResponse('Accès non autorisé à ce compte', 403);
        }

        DB::transaction(function () use ($validated, $compte) {
            $clientUpdates = [];
            $compteUpdates = [];

            // Préparer les mises à jour du compte
            if (isset($validated['titulaire'])) {
                $compteUpdates['titulaire'] = $validated['titulaire'];
            }

            // Préparer les mises à jour du client
            if (isset($validated['informationsClient'])) {
                $clientData = $validated['informationsClient'];

                if (isset($clientData['telephone'])) {
                    $clientUpdates['telephone'] = $clientData['telephone'];
                }

                if (isset($clientData['email'])) {
                    $clientUpdates['email'] = $clientData['email'];
                }

                if (isset($clientData['password'])) {
                    $clientUpdates['password'] = $clientData['password'];
                }

                if (isset($clientData['nci'])) {
                    $clientUpdates['nci'] = $clientData['nci'];
                }
            }

            // Mettre à jour le compte si nécessaire
            if (!empty($compteUpdates)) {
                $compte->update($compteUpdates);
            }

            // Mettre à jour le client si nécessaire
            if (!empty($clientUpdates)) {
                $compte->client->update($clientUpdates);
            }
        });

        // Recharger les relations
        $compte->load('client');

        // Formater la réponse selon la spécification
        $data = [
            'id' => $compte->id,
            'numeroCompte' => $compte->numero_compte,
            'titulaire' => $compte->titulaire,
            'type' => $compte->type,
            'solde' => $compte->solde,
            'devise' => $compte->devise,
            'dateCreation' => $compte->date_creation?->toISOString(),
            'statut' => $compte->statut,
            'metadata' => $compte->metadata,
        ];

        return $this->successResponse($data, 'Compte mis à jour avec succès');
    }

    /**
     * @OA\Delete(
     *     path="/api/comptes/{id}",
     *     tags={"Comptes"},
     *     summary="Fermer un compte",
     *     description="Ferme un compte bancaire (soft delete)",
     *     security={{"passport": {"write-comptes"}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID du compte",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Compte fermé avec succès"
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $compte = Compte::findOrFail($id);

        // Vérifier si le compte a un solde positif
        if ($compte->solde > 0) {
            return response()->json([
                'error' => 'Impossible de fermer un compte avec un solde positif. Veuillez d\'abord transférer les fonds.'
            ], 400);
        }

        $compte->update(['statut' => 'ferme']);
        $compte->delete();

        return response()->json(null, 204);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/comptes/archives/epargne",
     *     tags={"Comptes"},
     *     summary="Lister les comptes épargne archivés",
     *     description="Récupère la liste paginée des comptes épargne archivés depuis le cloud",
     *     security={{"passport": {"read-comptes"}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de la page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche par titulaire ou numéro",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"dateCreation", "solde", "titulaire"}, default="dateCreation")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Ordre",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     * @OA\Response(
     *         response=200,
     *         description="Liste des comptes épargne archivés récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Comptes épargne archivés récupérés avec succès"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001"),
     *                 @OA\Property(property="numeroCompte", type="string", example="SN2023000001"),
     *                 @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
     *                 @OA\Property(property="type", type="string", example="epargne"),
     *                 @OA\Property(property="solde", type="number", format="decimal", example=1250000),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
     *                 @OA\Property(property="statut", type="string", example="archive"),
     *                 @OA\Property(property="metadata", type="object",
     *                     @OA\Property(property="derniereModification", type="string", format="date-time"),
     *                     @OA\Property(property="version", type="integer"),
     *                     @OA\Property(property="dateArchivage", type="string", format="date-time")
     *                 )
     *             )),
     *             @OA\Property(property="pagination", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function archivedEpargne(Request $request): JsonResponse
    {
        try {
            // Récupération des paramètres de filtrage
            $filters = [
                'search' => $request->get('search'),
                'sort' => $request->get('sort', 'dateCreation'),
                'order' => $request->get('order', 'desc'),
            ];

            // Récupération des données depuis le cloud
            $archivedAccounts = $this->cloudStorageService->getArchivedEpargneComptes($filters);

            // Pagination manuelle
            $page = $request->get('page', 1);
            $limit = min($request->get('limit', 10), 100);
            $totalItems = count($archivedAccounts);
            $totalPages = ceil($totalItems / $limit);
            $offset = ($page - 1) * $limit;
            $paginatedData = array_slice($archivedAccounts, $offset, $limit);

            return $this->paginatedResponse(
                $paginatedData,
                [
                    'currentPage' => $page,
                    'totalPages' => $totalPages,
                    'totalItems' => $totalItems,
                    'itemsPerPage' => $limit,
                    'hasNext' => $page < $totalPages,
                    'hasPrevious' => $page > 1
                ],
                [
                    'self' => $request->fullUrl(),
                    'next' => $page < $totalPages ? $request->fullUrlWithQuery(['page' => $page + 1]) : null,
                    'previous' => $page > 1 ? $request->fullUrlWithQuery(['page' => $page - 1]) : null,
                    'first' => $request->fullUrlWithQuery(['page' => 1]),
                    'last' => $request->fullUrlWithQuery(['page' => $totalPages])
                ],
                'Comptes épargne archivés récupérés avec succès'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la récupération des comptes archivés: ' . $e->getMessage(),
                500
            );
        }
    }

}
