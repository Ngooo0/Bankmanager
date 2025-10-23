<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Transactions",
 *     description="API Endpoints pour la gestion des transactions bancaires"
 * )
 */
class TransactionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/transactions",
     *     tags={"Transactions"},
     *     summary="Lister toutes les transactions",
     *     description="Récupère la liste paginée de toutes les transactions",
     *     security={{"passport": {"read-transactions"}}},
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
     *         name="compte_id",
     *         in="query",
     *         description="Filtrer par compte UUID",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type de transaction",
     *         required=false,
     *         @OA\Schema(type="string", enum={"depot", "retrait", "virement_entrant", "virement_sortant", "frais", "interet"})
     *     ),
     *     @OA\Parameter(
     *         name="date_debut",
     *         in="query",
     *         description="Date de début (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_fin",
     *         in="query",
     *         description="Date de fin (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des transactions récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Transaction")),
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
        $query = Transaction::with('compte.client');

        if ($request->has('compte_id') && !empty($request->compte_id)) {
            $query->where('compte_id', $request->compte_id);
        }

        if ($request->has('type') && !empty($request->type)) {
            $query->where('type', $request->type);
        }

        if ($request->has('date_debut') && $request->has('date_fin')) {
            $query->whereBetween('date_transaction', [
                $request->date_debut . ' 00:00:00',
                $request->date_fin . ' 23:59:59'
            ]);
        }

        $transactions = $query->orderBy('date_transaction', 'desc')
                              ->paginate($request->get('per_page', 15));

        return response()->json($transactions);
    }

    /**
     * @OA\Post(
     *     path="/api/transactions",
     *     tags={"Transactions"},
     *     summary="Créer une nouvelle transaction",
     *     description="Effectue une nouvelle transaction sur un compte bancaire",
     *     security={{"passport": {"write-transactions"}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"compte_id", "type", "montant"},
     *             @OA\Property(property="compte_id", type="string", format="uuid", description="UUID du compte"),
     *             @OA\Property(property="type", type="string", enum={"depot", "retrait", "virement_entrant", "virement_sortant", "frais", "interet"}, description="Type de transaction"),
     *             @OA\Property(property="montant", type="number", format="decimal", description="Montant de la transaction"),
     *             @OA\Property(property="description", type="string", description="Description de la transaction"),
     *             @OA\Property(property="compte_destinataire", type="string", description="Numéro du compte destinataire (pour virements)"),
     *             @OA\Property(property="nom_destinataire", type="string", description="Nom du destinataire (pour virements)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transaction créée avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Transaction")
     *     ),
     *     @OA\Response(response=400, description="Données invalides ou solde insuffisant"),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'compte_id' => 'required|exists:comptes,id',
            'type' => 'required|in:depot,retrait,virement_entrant,virement_sortant,frais,interet',
            'montant' => 'required|numeric|min:0.01|max:999999999999.99',
            'description' => 'nullable|string|max:500',
            'compte_destinataire' => 'nullable|string|max:20',
            'nom_destinataire' => 'nullable|string|max:200',
        ]);

        $compte = Compte::findOrFail($request->compte_id);

        // Vérifier que le compte est actif
        if ($compte->statut !== 'actif') {
            return response()->json([
                'error' => 'Impossible d\'effectuer une transaction sur un compte ' . $compte->statut
            ], 400);
        }

        $montant = $request->montant;
        $nouveauSolde = $compte->solde;

        // Calculer le nouveau solde selon le type de transaction
        switch ($request->type) {
            case 'depot':
            case 'virement_entrant':
            case 'interet':
                $nouveauSolde += $montant;
                break;
            case 'retrait':
            case 'virement_sortant':
            case 'frais':
                if ($compte->solde < $montant) {
                    return response()->json([
                        'error' => 'Solde insuffisant pour effectuer cette transaction'
                    ], 400);
                }
                $nouveauSolde -= $montant;
                break;
        }

        DB::transaction(function () use ($request, $compte, $montant, $nouveauSolde) {
            // Créer la transaction
            $transaction = $compte->transactions()->create([
                'type' => $request->type,
                'montant' => $montant,
                'devise' => $compte->devise,
                'description' => $request->description,
                'date_transaction' => now(),
                'statut' => 'validee',
                'compte_destinataire' => $request->compte_destinataire,
                'nom_destinataire' => $request->nom_destinataire,
                'frais' => 0,
                'solde_apres' => $nouveauSolde,
            ]);

            // Mettre à jour le solde du compte
            $compte->update(['solde' => $nouveauSolde]);
        });

        return response()->json($compte->load('client'), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/transactions/{id}",
     *     tags={"Transactions"},
     *     summary="Afficher une transaction",
     *     description="Récupère les détails d'une transaction spécifique",
     *     security={{"passport": {"read-transactions"}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID de la transaction",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction trouvée",
     *         @OA\JsonContent(ref="#/components/schemas/Transaction")
     *     ),
     *     @OA\Response(response=404, description="Transaction non trouvée"),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $transaction = Transaction::with('compte.client')->findOrFail($id);

        return response()->json($transaction);
    }

    /**
     * @OA\Put(
     *     path="/api/transactions/{id}",
     *     tags={"Transactions"},
     *     summary="Annuler une transaction",
     *     description="Annule une transaction (si elle n'est pas déjà validée)",
     *     security={{"passport": {"write-transactions"}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID de la transaction",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction annulée avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Transaction")
     *     ),
     *     @OA\Response(response=400, description="Transaction ne peut pas être annulée"),
     *     @OA\Response(response=404, description="Transaction non trouvée"),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $transaction = Transaction::findOrFail($id);

        if ($transaction->statut !== 'en_attente') {
            return response()->json([
                'error' => 'Seules les transactions en attente peuvent être annulées'
            ], 400);
        }

        $transaction->update(['statut' => 'annulee']);

        return response()->json($transaction);
    }

    /**
     * @OA\Delete(
     *     path="/api/transactions/{id}",
     *     tags={"Transactions"},
     *     summary="Supprimer une transaction",
     *     description="Supprime une transaction (réservé aux administrateurs)",
     *     security={{"passport": {"write-transactions"}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID de la transaction",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Transaction supprimée avec succès"
     *     ),
     *     @OA\Response(response=404, description="Transaction non trouvée"),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $transaction = Transaction::findOrFail($id);
        $transaction->delete();

        return response()->json(null, 204);
    }
}
