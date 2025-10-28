#!/bin/sh

echo "🚀 Starting BankManager application..."

# Vérifier que PHP fonctionne
if ! php --version > /dev/null 2>&1; then
    echo "❌ PHP is not available"
    exit 1
fi

echo "✅ PHP is working"

# Vérifier que Laravel est installé
if [ ! -f "artisan" ]; then
    echo "❌ Laravel artisan file not found"
    exit 1
fi

echo "✅ Laravel artisan found"

# Nettoyer les caches avant de les régénérer
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Régénérer les caches
echo "🔄 Regenerating caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Tester la connectivité de base de données (optionnel)
echo "🔍 Testing database connectivity..."
if php artisan migrate:status > /dev/null 2>&1; then
    echo "✅ Database is accessible"

    # Exécuter les migrations si nécessaire
    echo "🗄️ Running migrations..."
    if php artisan migrate --force; then
        echo "✅ Migrations completed"
    else
        echo "⚠️ Migrations failed, continuing..."
    fi
else
    echo "⚠️ Database not accessible, skipping migrations"
fi

echo "🎯 Starting Laravel application..."

# Lancer la commande passée en argument
exec "$@"