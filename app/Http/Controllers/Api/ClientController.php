<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Clients",
 *     description="API Endpoints pour la gestion des clients"
 * )
 */
class ClientController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/clients",
     *     tags={"Clients"},
     *     summary="Lister tous les clients",
     *     description="Récupère la liste paginée de tous les clients",
     *     security={{"passport": {"read-clients"}}},
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
     *         name="search",
     *         in="query",
     *         description="Terme de recherche",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des clients récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Client")),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks"),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Client::with('comptes');

        if ($request->has('search') && !empty($request->search)) {
            $query->recherche($request->search);
        }

        $clients = $query->paginate($request->get('per_page', 15));

        return response()->json($clients);
    }

    /**
     * @OA\Post(
     *     path="/api/clients",
     *     tags={"Clients"},
     *     summary="Créer un nouveau client",
     *     description="Crée un nouveau client dans le système",
     *     security={{"passport": {"write-clients"}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreClientRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Client créé avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Client")
     *     ),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = Client::create($request->validated());

        return response()->json($client->load('comptes'), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/clients/{id}",
     *     tags={"Clients"},
     *     summary="Afficher un client",
     *     description="Récupère les détails d'un client spécifique",
     *     security={{"passport": {"read-clients"}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID du client",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/Client")
     *     ),
     *     @OA\Response(response=404, description="Client non trouvé"),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $client = Client::with(['comptes.transactions' => function ($query) {
            $query->latest()->limit(5);
        }])->findOrFail($id);

        return response()->json($client);
    }

    /**
     * @OA\Put(
     *     path="/api/clients/{id}",
     *     tags={"Clients"},
     *     summary="Mettre à jour un client",
     *     description="Met à jour les informations d'un client existant",
     *     security={{"passport": {"write-clients"}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID du client",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateClientRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client mis à jour avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Client")
     *     ),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=404, description="Client non trouvé"),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function update(UpdateClientRequest $request, string $id): JsonResponse
    {
        $client = Client::findOrFail($id);
        $client->update($request->validated());

        return response()->json($client->load('comptes'));
    }

    /**
     * @OA\Delete(
     *     path="/api/clients/{id}",
     *     tags={"Clients"},
     *     summary="Supprimer un client",
     *     description="Supprime un client du système (soft delete)",
     *     security={{"passport": {"write-clients"}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID du client",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Client supprimé avec succès"
     *     ),
     *     @OA\Response(response=404, description="Client non trouvé"),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $client = Client::findOrFail($id);
        $client->delete();

        return response()->json(null, 204);
    }
}
