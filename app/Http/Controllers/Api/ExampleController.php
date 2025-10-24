<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Exemples",
 *     description="Endpoints d'exemple pour démonstration"
 * )
 */
class ExampleController extends Controller
{
    /**
     * @OA\Tag(
     *     name="Exemples",
     *     description="Endpoints d'exemple pour démonstration"
     * )
     */
    public function __construct()
    {
        // Constructeur vide pour les annotations
    }

    /**
     * @OA\Get(
     *   path="/api/v1/clients",
     *   summary="Lister les clients",
     *   description="Récupère la liste paginée des clients",
     *   tags={"Clients"},
     *   security={{"passport": {"read-clients"}}},
     *   @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="Numéro de la page",
     *     required=false,
     *     @OA\Schema(type="integer", default=1)
     *   ),
     *   @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Nombre d'éléments par page",
     *     required=false,
     *     @OA\Schema(type="integer", default=10, maximum=100)
     *   ),
     *   @OA\Parameter(
     *     name="search",
     *     in="query",
     *     description="Recherche par nom ou email",
     *     required=false,
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Liste des clients récupérée avec succès",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Clients récupérés avec succès"),
     *       @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Client")),
     *       @OA\Property(property="pagination", ref="#/components/schemas/PaginationMeta"),
     *       @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *     )
     *   ),
     *   @OA\Response(response=401, description="Non authentifié"),
     *   @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function indexClients(Request $request)
    {
        // Logique d'exemple - à remplacer par votre implémentation
        return response()->json([
            'success' => true,
            'message' => 'Endpoint d\'exemple - Liste des clients',
            'data' => [],
            'pagination' => [
                'currentPage' => 1,
                'totalPages' => 1,
                'totalItems' => 0,
                'itemsPerPage' => 10,
                'hasNext' => false,
                'hasPrevious' => false
            ],
            'links' => [
                'self' => '/api/v1/clients?page=1&limit=10',
                'first' => '/api/v1/clients?page=1&limit=10',
                'last' => '/api/v1/clients?page=1&limit=10'
            ]
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/v1/clients",
     *   summary="Créer un client",
     *   description="Crée un nouveau client dans le système",
     *   tags={"Clients"},
     *   security={{"passport": {"write-clients"}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"nom_complet", "email", "telephone"},
     *       @OA\Property(property="nom_complet", type="string", example="Amadou Diallo"),
     *       @OA\Property(property="email", type="string", format="email", example="amadou.diallo@example.com"),
     *       @OA\Property(property="telephone", type="string", example="+221771234567"),
     *       @OA\Property(property="date_naissance", type="string", format="date", example="1990-01-15"),
     *       @OA\Property(property="adresse", type="string", example="Dakar, Sénégal")
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Client créé avec succès",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Client créé avec succès"),
     *       @OA\Property(property="data", ref="#/components/schemas/Client")
     *     )
     *   ),
     *   @OA\Response(response=400, description="Données invalides"),
     *   @OA\Response(response=401, description="Non authentifié"),
     *   @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function storeClient(Request $request)
    {
        // Logique d'exemple - à remplacer par votre implémentation
        return response()->json([
            'success' => true,
            'message' => 'Endpoint d\'exemple - Client créé',
            'data' => [
                'id' => 'example-uuid',
                'nom_complet' => $request->nom_complet ?? 'Client Exemple',
                'email' => $request->email ?? 'client@example.com',
                'telephone' => $request->telephone ?? '+221771234567',
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString()
            ]
        ], 201);
    }
}