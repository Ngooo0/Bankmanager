<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Compte;
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
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Compte")),
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
        $query = Compte::with('client');

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

        return response()->json($comptes);
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
}
