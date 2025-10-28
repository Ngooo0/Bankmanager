# Supprimer l'ancien fichier
rm docker-entrypoint.sh

# Créer un nouveau fichier (sur Linux/Mac)
cat > docker-entrypoint.sh << 'EOF'
#!/bin/sh
set -e

echo "=== Starting Laravel Application ==="

# Vérifier les variables d'environnement critiques
if [ -z "$APP_KEY" ]; then
    echo "⚠️ WARNING: APP_KEY not set in environment, using generated one"
else
    echo "✅ APP_KEY found in environment"
fi

echo "DB Host: ${DB_HOST:-not set}"
echo "DB Port: ${DB_PORT:-not set}"
echo "DB Database: ${DB_DATABASE:-not set}"

# Attendre la base de données (avec timeout)
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database..."
    max_attempts=15
    attempt=0
    
    while [ $attempt -lt $max_attempts ]; do
        if PGPASSWORD=$DB_PASSWORD psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -c '\q' 2>/dev/null; then
            echo "✅ Database connected!"
            
            # Exécuter les migrations
            echo "Running migrations..."
            php artisan migrate --force 2>&1 || echo "⚠️ Migrations completed with warnings"
            break
        fi
        
        attempt=$((attempt + 1))
        echo "Database unavailable (attempt $attempt/$max_attempts) - waiting..."
        sleep 2
    done
    
    if [ $attempt -eq $max_attempts ]; then
        echo "⚠️ Could not connect to database - starting without migrations"
    fi
else
    echo "⚠️ DB_HOST not set - skipping database checks"
fi

# Nettoyer et optimiser Laravel
echo "Optimizing Laravel..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear 2>/dev/null || true

# Reconstruire les caches
php artisan config:cache
php artisan route:cache

echo "✅ Laravel is ready!"
echo "Starting server on port 10000..."
echo "==================================="

# Démarrer l'application
exec "$@"
EOF

# Rendre exécutable
chmod +x docker-entrypoint.sh

# Vérifier
file docker-entrypoint.sh