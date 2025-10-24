#!/bin/bash

# Script pour régénérer la documentation Swagger
# Utilisation: ./generate-swagger.sh

echo "🔄 Régénération de la documentation Swagger..."

# Nettoyer le cache des routes
php artisan route:clear

# Générer la documentation Swagger
php artisan l5-swagger:generate

echo "✅ Documentation générée avec succès !"
echo "📖 Accédez à la documentation : http://localhost:8001/api/documentation"
echo "📄 JSON disponible : http://localhost:8001/docs/api-docs.json"