<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Compte;
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
    /**
     * @OA\Get(
     *     path="/api/comptes",
     *     tags={"Comptes"},
     *     summary="Lister tous les comptes",
     *     description="Récupère la liste paginée de tous les comptes bancaires",
     *     security={{"passport": {"read-comptes"}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de la page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Parameter(
     *         name="client_id",
     *         in="query",
     *         description="Filtrer par client UUID",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type de compte",
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
     * @OA\Response(
     *         response=200,
     *         description="Liste des comptes récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Données récupérées avec succès"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Compte")),
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
        $query = Compte::with('client')->nonSupprimes();

        if ($request->has('client_id') && !empty($request->client_id)) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->has('type') && !empty($request->type)) {
            $query->where('type', $request->type);
        }

        if ($request->has('statut') && !empty($request->statut)) {
            $query->where('statut', $request->statut);
        }

        $comptes = $query->paginate($request->get('per_page', 15));

        return $this->paginatedResponse(
            $comptes->items(),
            [
                'currentPage' => $comptes->currentPage(),
                'totalPages' => $comptes->lastPage(),
                'totalItems' => $comptes->total(),
                'itemsPerPage' => $comptes->perPage(),
                'hasNext' => $comptes->hasMorePages(),
                'hasPrevious' => $comptes->currentPage() > 1
            ],
            [
                'self' => $comptes->url($comptes->currentPage()),
                'next' => $comptes->nextPageUrl(),
                'previous' => $comptes->previousPageUrl(),
                'first' => $comptes->url(1),
                'last' => $comptes->url($comptes->lastPage())
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
     *     path="/api/comptes/{id}",
     *     tags={"Comptes"},
     *     summary="Afficher un compte",
     *     description="Récupère les détails d'un compte bancaire spécifique",
     *     security={{"passport": {"read-comptes"}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID du compte",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/Compte")
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $compte = Compte::with(['client', 'transactions' => function ($query) {
            $query->latest()->limit(10);
        }])->findOrFail($id);

        return response()->json($compte);
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
            // Simulation de récupération depuis le cloud (remplacer par l'implémentation réelle)
            $cloudData = $this->fetchArchivedEpargneFromCloud($request);

            return $this->paginatedResponse(
                $cloudData['data'],
                $cloudData['pagination'],
                $cloudData['links'],
                'Comptes épargne archivés récupérés avec succès'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la récupération des comptes archivés: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Récupère les comptes épargne archivés depuis le cloud
     */
    private function fetchArchivedEpargneFromCloud(Request $request): array
    {
        // Simulation des données (remplacer par l'appel réel au cloud)
        $page = $request->get('page', 1);
        $limit = min($request->get('limit', 10), 100);
        $search = $request->get('search');
        $sort = $request->get('sort', 'dateCreation');
        $order = $request->get('order', 'desc');

        // Ici, intégrer l'appel au service cloud (AWS S3, Google Cloud, etc.)
        // Exemple avec AWS SDK:
        // $s3Client = new S3Client([...]);
        // $result = $s3Client->getObject(['Bucket' => 'archived-accounts', 'Key' => 'epargne.json']);

        // Simulation des données archivées
        $archivedAccounts = [
            [
                'id' => '550e8400-e29b-41d4-a716-446655440001',
                'numeroCompte' => 'SN2023000001',
                'titulaire' => 'Amadou Diallo',
                'type' => 'epargne',
                'solde' => 1250000,
                'devise' => 'FCFA',
                'dateCreation' => '2023-03-15T00:00:00Z',
                'statut' => 'archive',
                'metadata' => [
                    'derniereModification' => '2023-06-10T14:30:00Z',
                    'version' => 1,
                    'dateArchivage' => '2023-12-31T23:59:59Z'
                ]
            ],
            // Plus de données simulées...
        ];

        // Appliquer les filtres
        if ($search) {
            $archivedAccounts = array_filter($archivedAccounts, function ($account) use ($search) {
                return stripos($account['titulaire'], $search) !== false ||
                       stripos($account['numeroCompte'], $search) !== false;
            });
        }

        // Trier
        usort($archivedAccounts, function ($a, $b) use ($sort, $order) {
            $valueA = $a[$sort] ?? $a['dateCreation'];
            $valueB = $b[$sort] ?? $b['dateCreation'];

            if ($order === 'asc') {
                return $valueA <=> $valueB;
            } else {
                return $valueB <=> $valueA;
            }
        });

        // Pagination
        $totalItems = count($archivedAccounts);
        $totalPages = ceil($totalItems / $limit);
        $offset = ($page - 1) * $limit;
        $paginatedData = array_slice($archivedAccounts, $offset, $limit);

        return [
            'data' => $paginatedData,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalItems' => $totalItems,
                'itemsPerPage' => $limit,
                'hasNext' => $page < $totalPages,
                'hasPrevious' => $page > 1
            ],
            'links' => [
                'self' => "/api/v1/comptes/archives/epargne?page={$page}&limit={$limit}",
                'next' => $page < $totalPages ? "/api/v1/comptes/archives/epargne?page=" . ($page + 1) . "&limit={$limit}" : null,
                'previous' => $page > 1 ? "/api/v1/comptes/archives/epargne?page=" . ($page - 1) . "&limit={$limit}" : null,
                'first' => "/api/v1/comptes/archives/epargne?page=1&limit={$limit}",
                'last' => "/api/v1/comptes/archives/epargne?page={$totalPages}&limit={$limit}"
            ]
        ];
    }
}
