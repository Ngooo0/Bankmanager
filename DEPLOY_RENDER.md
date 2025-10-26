# Déploiement Render — Swagger UI (BankManager)

Ce fichier décrit les étapes recommandées pour rendre Swagger UI fonctionnelle lors du déploiement sur Render.

1) Variables d'environnement (Render → Settings → Environment):

- `APP_URL` : `https://votre-app-render.onrender.com` (URL publique de votre service)
- `APP_ENV` : `production`
- `APP_KEY` : (laisser vide si vous voulez que le build le génère)
- `L5_SWAGGER_USE_ABSOLUTE_PATH` : `true` (recommandé en production)
- `L5_SWAGGER_GENERATE_ALWAYS` : `false` (nous générons durant le build)
 - `SWAGGER_ENABLED` : `true` pour exposer l'UI en production (sinon laisser à `false`)

2) Build Command (Render) :

Configurez la commande de build dans Render pour appeler le script `render-build.sh` que l'on a ajouté au repo :

```bash
bash render-build.sh
```

Ce script :
- installe les dépendances composer
- génère `APP_KEY` si besoin
- clear/cache la config (important pour prendre `APP_URL` en compte)
- crée `storage/api-docs` et ajuste les permissions
- exécute `php artisan l5-swagger:generate`
- met en cache views/routes

3) Start Command (Render) :

Si vous utilisez le `Procfile` fourni (`web: vendor/bin/heroku-php-apache2 public/`), gardez-le. Render lancera Apache qui servira l'application.

Alternativement, vous pouvez démarrer via `start.sh` présent à la racine (ce script exécute migrations et génère la doc) :

```bash
bash start.sh
```

Mais attention : `start.sh` exécute `php artisan serve` (dev server) — préférez Apache (Procfile) pour la production.

4) Vérifications après déploiement :

Vérifiez ces URLs :

- UI HTML : `https://votre-app-render.onrender.com/api/docs`  → 200 OK (text/html)
- JSON spec : `https://votre-app-render.onrender.com/api/documentation` → 200 OK (application/json)
- Asset CSS : `https://votre-app-render.onrender.com/api/documentation/asset/swagger-ui.css` → 200 OK

5) Sécurité (recommandé)

- Protégez l'accès à `/api/docs` en production via middleware (Basic Auth, IP allow-list, ou middleware Laravel qui vérifie une clé d'environnement). Exemple : créer un middleware `EnsureSwaggerAllowed` et l'ajouter dans `config/l5-swagger.php` → `defaults.routes.middleware.docs`.

6) Remarques

- Si `L5_SWAGGER_USE_ABSOLUTE_PATH=true`, assurez-vous que `APP_URL` est correct avant d'exécuter `l5-swagger:generate`.
- Le fichier `storage/api-docs/api-docs.json` sera généré pendant le build et utilisé par l'UI.

---

Si vous voulez, je peux :
- ajouter un middleware d'accès Swagger et la configuration correspondante (ex: auth basic via env var),
- ajouter une route web conviviale `/documentation` qui redirige vers `config('app.url').'/api/docs'`.

Dites-moi si je dois appliquer ces options maintenant.
