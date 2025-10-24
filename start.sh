#!/bin/bash

# Script de démarrage pour Render
echo "🚀 Démarrage de BankManager API..."

# Attendre que la base de données soit prête
echo "⏳ Attente de la base de données..."
sleep 15

# Générer la clé d'application si nécessaire
echo "🔑 Génération de la clé d'application..."
php artisan key:generate --force --no-interaction

# Vider les caches
echo "🧹 Nettoyage des caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Exécuter les migrations
echo "🗄️ Exécution des migrations..."
php artisan migrate --force --no-interaction

# Générer la documentation Swagger
echo "📚 Génération de la documentation Swagger..."
php artisan l5-swagger:generate --no-interaction

# Optimisations pour la production
echo "⚡ Optimisations pour la production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Application prête !"
echo "🌐 Démarrage du serveur sur le port 10000..."

# Démarrer l'application
exec php artisan serve --host=0.0.0.0 --port=10000