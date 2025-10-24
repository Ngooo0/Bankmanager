# Documentation Swagger - BankManager API

## Vue d'ensemble
Cette configuration OpenAPI/Swagger fournit une documentation interactive pour l'API BankManager.

## Accès à la documentation

### Interface interactive
- **URL** : `http://localhost:8001/api/documentation`
- **Description** : Interface Swagger UI pour explorer et tester les endpoints

### Fichier JSON brut
- **URL** : `http://localhost:8001/docs/api-docs.json`
- **Description** : Spécification OpenAPI 3.0 en format JSON

## Régénération de la documentation

### Méthode 1 : Commande Artisan
```bash
php artisan l5-swagger:generate
```

### Méthode 2 : Script fourni
```bash
./generate-swagger.sh
```

## Configuration

### Variables d'environnement
Ajoutez dans votre `.env` :
```env
# Désactiver la génération automatique en production
L5_SWAGGER_GENERATE_ALWAYS=false
```

### Fichiers de configuration
- `config/l5-swagger.php` : Configuration principale
- Routes définies dans `routes/api.php`

## Annotations OpenAPI

### Où ajouter les annotations
Ajoutez les annotations dans vos contrôleurs dans `app/Http/Controllers/Api/`.

### Exemple d'annotation
```php
/**
 * @OA\Get(
 *   path="/api/v1/clients",
 *   summary="Lister les clients",
 *   tags={"Clients"},
 *   @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
 *   @OA\Response(response=200, description="OK")
 * )
 */
public function index(Request $request)
{
    // Votre logique ici
}
```

### Éléments importants
- `@OA\Info` : Informations générales de l'API (une seule fois)
- `@OA\Tag` : Groupement des endpoints
- `@OA\Parameter` : Paramètres de requête
- `@OA\Response` : Réponses possibles
- `@OA\Schema` : Définition des modèles de données

## Sécurité
- Authentification OAuth2 avec Laravel Passport
- Scopes définis : `read-clients`, `write-clients`, `read-comptes`, `write-comptes`

## Endpoints disponibles
- `GET /api/v1/comptes` - Lister les comptes
- `GET /api/v1/comptes/archives/epargne` - Comptes épargne archivés
- `GET /api/v1/clients` - Lister les clients (exemple)
- `POST /api/v1/clients` - Créer un client (exemple)

## Validation
✅ Interface accessible sans erreur
✅ Endpoints testables via "Try it out"
✅ Génération rapide via script fourni
✅ Projet fonctionnel après configuration