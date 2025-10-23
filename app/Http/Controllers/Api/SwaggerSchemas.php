<?php

namespace App\Http\Controllers\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="BankManager API",
 *     version="1.0.0",
 *     description="API de gestion bancaire pour BankManager",
 *     @OA\Contact(
 *         email="contact@bankmanager.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Serveur de développement"
 * )
 *
 * @OA\Schema(
 *     schema="Client",
 *     type="object",
 *     title="Client",
 *     description="Modèle représentant un client de la banque",
 *     @OA\Property(property="id", type="string", format="uuid", description="UUID du client"),
 *     @OA\Property(property="prenom", type="string", maxLength=100, description="Prénom du client"),
 *     @OA\Property(property="nom", type="string", maxLength=100, description="Nom du client"),
 *     @OA\Property(property="nom_complet", type="string", description="Nom complet du client"),
 *     @OA\Property(property="email", type="string", format="email", description="Email du client"),
 *     @OA\Property(property="telephone", type="string", maxLength=20, nullable=true, description="Téléphone du client"),
 *     @OA\Property(property="adresse", type="string", maxLength=255, nullable=true, description="Adresse du client"),
 *     @OA\Property(property="ville", type="string", maxLength=100, nullable=true, description="Ville du client"),
 *     @OA\Property(property="pays", type="string", maxLength=100, description="Pays du client"),
 *     @OA\Property(property="code_postal", type="string", maxLength=10, nullable=true, description="Code postal"),
 *     @OA\Property(property="numero_identification", type="string", description="Numéro d'identification (CIN, Passeport, etc.)"),
 *     @OA\Property(property="type_identification", type="string", enum={"CIN", "Passeport", "Permis de conduire"}, description="Type d'identification"),
 *     @OA\Property(property="date_naissance", type="string", format="date", description="Date de naissance"),
 *     @OA\Property(property="age", type="integer", description="Âge du client"),
 *     @OA\Property(property="sexe", type="string", enum={"M", "F", "Autre"}, description="Sexe du client"),
 *     @OA\Property(property="profession", type="string", maxLength=150, nullable=true, description="Profession du client"),
 *     @OA\Property(property="employeur", type="string", maxLength=150, nullable=true, description="Employeur du client"),
 *     @OA\Property(property="revenu_mensuel", type="number", format="decimal", nullable=true, description="Revenu mensuel"),
 *     @OA\Property(property="statut", type="string", enum={"Actif", "Inactif", "Suspendu", "Bloqué"}, description="Statut du client"),
 *     @OA\Property(property="notes", type="string", nullable=true, description="Notes sur le client"),
 *     @OA\Property(property="created_at", type="string", format="datetime", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="datetime", description="Date de mise à jour"),
 *     @OA\Property(property="comptes", type="array", @OA\Items(ref="#/components/schemas/Compte"), description="Comptes du client")
 * )
 *
 * @OA\Schema(
 *     schema="Compte",
 *     type="object",
 *     title="Compte",
 *     description="Modèle représentant un compte bancaire",
 *     @OA\Property(property="id", type="string", format="uuid", description="UUID du compte"),
 *     @OA\Property(property="numero_compte", type="string", maxLength=20, description="Numéro du compte"),
 *     @OA\Property(property="titulaire", type="string", maxLength=200, description="Nom du titulaire"),
 *     @OA\Property(property="type", type="string", enum={"epargne", "cheque"}, description="Type de compte"),
 *     @OA\Property(property="solde", type="number", format="decimal", description="Solde du compte"),
 *     @OA\Property(property="solde_formate", type="string", description="Solde formaté avec devise"),
 *     @OA\Property(property="devise", type="string", maxLength=3, description="Devise du compte"),
 *     @OA\Property(property="date_creation", type="string", format="date", description="Date de création du compte"),
 *     @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, description="Statut du compte"),
 *     @OA\Property(property="created_at", type="string", format="datetime", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="datetime", description="Date de mise à jour"),
 *     @OA\Property(property="client", ref="#/components/schemas/Client", description="Client propriétaire du compte"),
 *     @OA\Property(property="transactions", type="array", @OA\Items(ref="#/components/schemas/Transaction"), description="Transactions du compte")
 * )
 *
 * @OA\Schema(
 *     schema="Transaction",
 *     type="object",
 *     title="Transaction",
 *     description="Modèle représentant une transaction bancaire",
 *     @OA\Property(property="id", type="string", format="uuid", description="UUID de la transaction"),
 *     @OA\Property(property="type", type="string", enum={"depot", "retrait", "virement_entrant", "virement_sortant", "frais", "interet"}, description="Type de transaction"),
 *     @OA\Property(property="type_libelle", type="string", description="Libellé du type de transaction"),
 *     @OA\Property(property="montant", type="number", format="decimal", description="Montant de la transaction"),
 *     @OA\Property(property="montant_formate", type="string", description="Montant formaté avec devise"),
 *     @OA\Property(property="devise", type="string", maxLength=3, description="Devise de la transaction"),
 *     @OA\Property(property="description", type="string", nullable=true, description="Description de la transaction"),
 *     @OA\Property(property="date_transaction", type="string", format="datetime", description="Date et heure de la transaction"),
 *     @OA\Property(property="statut", type="string", enum={"en_attente", "validee", "annulee", "echouee"}, description="Statut de la transaction"),
 *     @OA\Property(property="compte_destinataire", type="string", nullable=true, description="Numéro du compte destinataire"),
 *     @OA\Property(property="nom_destinataire", type="string", nullable=true, description="Nom du destinataire"),
 *     @OA\Property(property="frais", type="number", format="decimal", description="Frais de transaction"),
 *     @OA\Property(property="solde_apres", type="number", format="decimal", description="Solde après transaction"),
 *     @OA\Property(property="created_at", type="string", format="datetime", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="datetime", description="Date de mise à jour"),
 *     @OA\Property(property="compte", ref="#/components/schemas/Compte", description="Compte associé à la transaction")
 * )
 *
 * @OA\Schema(
 *     schema="StoreClientRequest",
 *     type="object",
 *     title="StoreClientRequest",
 *     description="Données pour créer un nouveau client",
 *     required={"prenom", "nom", "email", "numero_identification", "type_identification", "date_naissance", "sexe", "pays"},
 *     @OA\Property(property="prenom", type="string", maxLength=100, description="Prénom du client"),
 *     @OA\Property(property="nom", type="string", maxLength=100, description="Nom du client"),
 *     @OA\Property(property="email", type="string", format="email", description="Email du client"),
 *     @OA\Property(property="telephone", type="string", maxLength=20, nullable=true, description="Téléphone du client"),
 *     @OA\Property(property="adresse", type="string", maxLength=255, nullable=true, description="Adresse du client"),
 *     @OA\Property(property="ville", type="string", maxLength=100, nullable=true, description="Ville du client"),
 *     @OA\Property(property="pays", type="string", maxLength=100, description="Pays du client"),
 *     @OA\Property(property="code_postal", type="string", maxLength=10, nullable=true, description="Code postal"),
 *     @OA\Property(property="numero_identification", type="string", description="Numéro d'identification"),
 *     @OA\Property(property="type_identification", type="string", enum={"CIN", "Passeport", "Permis de conduire"}, description="Type d'identification"),
 *     @OA\Property(property="date_naissance", type="string", format="date", description="Date de naissance"),
 *     @OA\Property(property="sexe", type="string", enum={"M", "F", "Autre"}, description="Sexe du client"),
 *     @OA\Property(property="profession", type="string", maxLength=150, nullable=true, description="Profession du client"),
 *     @OA\Property(property="employeur", type="string", maxLength=150, nullable=true, description="Employeur du client"),
 *     @OA\Property(property="revenu_mensuel", type="number", format="decimal", nullable=true, description="Revenu mensuel"),
 *     @OA\Property(property="notes", type="string", nullable=true, description="Notes sur le client")
 * )
 *
 * @OA\Schema(
 *     schema="UpdateClientRequest",
 *     type="object",
 *     title="UpdateClientRequest",
 *     description="Données pour mettre à jour un client",
 *     @OA\Property(property="prenom", type="string", maxLength=100, nullable=true, description="Prénom du client"),
 *     @OA\Property(property="nom", type="string", maxLength=100, nullable=true, description="Nom du client"),
 *     @OA\Property(property="email", type="string", format="email", nullable=true, description="Email du client"),
 *     @OA\Property(property="telephone", type="string", maxLength=20, nullable=true, description="Téléphone du client"),
 *     @OA\Property(property="adresse", type="string", maxLength=255, nullable=true, description="Adresse du client"),
 *     @OA\Property(property="ville", type="string", maxLength=100, nullable=true, description="Ville du client"),
 *     @OA\Property(property="pays", type="string", maxLength=100, nullable=true, description="Pays du client"),
 *     @OA\Property(property="code_postal", type="string", maxLength=10, nullable=true, description="Code postal"),
 *     @OA\Property(property="numero_identification", type="string", nullable=true, description="Numéro d'identification"),
 *     @OA\Property(property="type_identification", type="string", enum={"CIN", "Passeport", "Permis de conduire"}, nullable=true, description="Type d'identification"),
 *     @OA\Property(property="date_naissance", type="string", format="date", nullable=true, description="Date de naissance"),
 *     @OA\Property(property="sexe", type="string", enum={"M", "F", "Autre"}, nullable=true, description="Sexe du client"),
 *     @OA\Property(property="profession", type="string", maxLength=150, nullable=true, description="Profession du client"),
 *     @OA\Property(property="employeur", type="string", maxLength=150, nullable=true, description="Employeur du client"),
 *     @OA\Property(property="revenu_mensuel", type="number", format="decimal", nullable=true, description="Revenu mensuel"),
 *     @OA\Property(property="statut", type="string", enum={"Actif", "Inactif", "Suspendu", "Bloqué"}, nullable=true, description="Statut du client"),
 *     @OA\Property(property="notes", type="string", nullable=true, description="Notes sur le client")
 * )
 *
 * @OA\Schema(
 *     schema="PaginationLinks",
 *     type="object",
 *     title="PaginationLinks",
 *     description="Liens de pagination",
 *     @OA\Property(property="first", type="string", description="Lien vers la première page"),
 *     @OA\Property(property="last", type="string", description="Lien vers la dernière page"),
 *     @OA\Property(property="prev", type="string", nullable=true, description="Lien vers la page précédente"),
 *     @OA\Property(property="next", type="string", nullable=true, description="Lien vers la page suivante")
 * )
 *
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     type="object",
 *     title="PaginationMeta",
 *     description="Métadonnées de pagination",
 *     @OA\Property(property="current_page", type="integer", description="Page actuelle"),
 *     @OA\Property(property="from", type="integer", description="Premier élément de la page"),
 *     @OA\Property(property="last_page", type="integer", description="Dernière page"),
 *     @OA\Property(property="links", type="array", @OA\Items(type="object"), description="Liens de pagination"),
 *     @OA\Property(property="path", type="string", description="URL de base"),
 *     @OA\Property(property="per_page", type="integer", description="Éléments par page"),
 *     @OA\Property(property="to", type="integer", description="Dernier élément de la page"),
 *     @OA\Property(property="total", type="integer", description="Nombre total d'éléments")
 * )
 */
class SwaggerSchemas
{
    // Cette classe ne contient que des annotations Swagger
    // Elle n'a pas besoin de méthodes
}