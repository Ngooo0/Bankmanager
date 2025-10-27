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
     *     path="/api/comptes",
     *     tags={"Comptes"},
     *     summary="Créer un nouveau compte",
     *     description="Crée un nouveau compte bancaire pour un client existant",
     *     security={{"passport": {"write-comptes"}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"client_id", "type"},
     *             @OA\Property(property="client_id", type="string", format="uuid", description="UUID du client"),
     *             @OA\Property(property="type", type="string", enum={"epargne", "cheque"}, description="Type de compte"),
     *             @OA\Property(property="solde_initial", type="number", format="decimal", description="Solde initial", default=0),
     *             @OA\Property(property="devise", type="string", maxLength=3, description="Devise", default="XOF")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Compte")
     *     ),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=404, description="Client non trouvé"),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'type' => 'required|in:epargne,cheque',
            'solde_initial' => 'numeric|min:0|max:999999999999.99',
            'devise' => 'string|max:3'
        ]);

        $client = Client::findOrFail($request->client_id);

        DB::transaction(function () use ($request, $client, &$compte) {
            $compte = Compte::create([
                'client_id' => $client->id,
                'titulaire' => $client->nom_complet,
                'type' => $request->type,
                'solde' => $request->solde_initial ?? 0,
                'devise' => $request->devise ?? 'XOF',
                'date_creation' => now(),
                'statut' => 'actif',
            ]);

            // Créer une transaction d'ouverture si solde initial > 0
            if ($compte->solde > 0) {
                $compte->transactions()->create([
                    'type' => 'depot',
                    'montant' => $compte->solde,
                    'devise' => $compte->devise,
                    'description' => 'Ouverture du compte avec solde initial',
                    'date_transaction' => now(),
                    'statut' => 'validee',
                    'solde_apres' => $compte->solde,
                ]);
            }
        });

        return response()->json($compte->load('client'), 201);
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
     * @OA\Put(
     *     path="/api/comptes/{id}",
     *     tags={"Comptes"},
     *     summary="Mettre à jour un compte",
     *     description="Met à jour les informations d'un compte bancaire",
     *     security={{"passport": {"write-comptes"}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID du compte",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, description="Nouveau statut du compte"),
     *             @OA\Property(property="notes", type="string", description="Notes sur le compte")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte mis à jour avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Compte")
     *     ),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'statut' => 'sometimes|in:actif,bloque,ferme',
        ]);

        $compte = Compte::findOrFail($id);
        $compte->update($request->only(['statut']));

        return response()->json($compte->load('client'));
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
